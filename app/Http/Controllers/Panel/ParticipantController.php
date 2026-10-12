<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\ChallengeTask;
use App\Models\Participant;
use App\Support\TextList;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ParticipantController extends Controller
{
    public const FILTERS = [
        'all' => 'همه',
        'today' => 'امروز تیک زده‌اند',
        'recent' => '۳ روز اخیر فعال',
        'inactive' => 'بی‌فعال (بیش از ۳ روز)',
        'notstarted' => 'هنوز شروع نکرده‌اند',
    ];

    public const SORTS = [
        'progress' => 'بیشترین پیشرفت',
        'weakest' => 'کمترین پیشرفت',
        'activity' => 'آخرین فعالیت',
        'seen' => 'آخرین بازدید',
        'name' => 'نام (الفبا)',
    ];

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $challengeFilter = (string) $request->query('challenge', '');
        $status = (string) $request->query('status', 'all');
        $sort = (string) $request->query('sort', 'progress');

        $status = array_key_exists($status, self::FILTERS) ? $status : 'all';
        $sort = array_key_exists($sort, self::SORTS) ? $sort : 'progress';

        $admin = Auth::guard('admin')->user();

        $participants = Participant::query()
            ->visibleToAdmin($admin)
            ->with(['challenge.tasks'])
            ->withCount(['completions as completed_count' => fn ($query) => $query->whereNotNull('completed_at')])
            ->withMax('completions as last_completed_at', 'completed_at')
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->when($challengeFilter !== '', fn ($query) => $query->whereHas(
                'challenge',
                fn ($q) => $q->where('slug', $challengeFilter)->orWhere('id', $challengeFilter)
            ))
            ->when($status === 'today', fn ($query) => $query->whereHas(
                'completions',
                fn ($q) => $q->whereNotNull('completed_at')->whereDate('completed_at', today())
            ))
            ->when($status === 'recent', fn ($query) => $query->whereHas(
                'completions',
                fn ($q) => $q->whereNotNull('completed_at')->where('completed_at', '>=', Carbon::now()->subDays(3))
            ))
            ->when($status === 'inactive', fn ($query) => $query
                ->whereHas('completions', fn ($q) => $q->whereNotNull('completed_at'))
                ->whereDoesntHave('completions', fn ($q) => $q->whereNotNull('completed_at')->where('completed_at', '>=', Carbon::now()->subDays(3))))
            ->when($status === 'notstarted', fn ($query) => $query->whereDoesntHave('completions', fn ($q) => $q->whereNotNull('completed_at')))
            ->get();

        // مرتب‌سازی پایدار: اول بر اساس نام، بعد بر اساس معیار انتخابی
        $participants = $participants->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE);

        $participants = match ($sort) {
            'name' => $participants,
            'weakest' => $participants->sortBy(fn (Participant $participant) => $participant->progressPercent()),
            'activity' => $participants->sortByDesc(fn (Participant $participant) => (string) $participant->last_completed_at),
            'seen' => $participants->sortByDesc(fn (Participant $participant) => (string) $participant->last_seen_at),
            default => $participants->sortByDesc(fn (Participant $participant) => $participant->progressPercent()),
        };

        $page = Paginator::resolveCurrentPage();
        $perPage = 20;

        $paginated = new LengthAwarePaginator(
            $participants->forPage($page, $perPage)->values(),
            $participants->count(),
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath(), 'query' => $request->query()],
        );

        return view('panel.participants.index', [
            'participants' => $paginated,
            'challenges' => Challenge::query()->visibleToAdmin($admin)->orderBy('title')->get(),
            'search' => $search,
            'challengeFilter' => $challengeFilter,
            'status' => $status,
            'sort' => $sort,
            'stats' => $this->stats(),
        ]);
    }

    /**
     * آمار کلی شرکت‌کننده‌ها برای بالای صفحه مدیریت.
     *
     * @return array<string, int>
     */
    private function stats(): array
    {
        $all = Participant::query()
            ->withCount(['completions as completed_count' => fn ($query) => $query->whereNotNull('completed_at')])
            ->get();

        $todayCount = Participant::query()
            ->whereHas('completions', fn ($q) => $q->whereNotNull('completed_at')->whereDate('completed_at', today()))
            ->count();

        $activeRecently = Participant::query()
            ->whereHas('completions', fn ($q) => $q->whereNotNull('completed_at')->where('completed_at', '>=', Carbon::now()->subDays(3)))
            ->count();

        $notStarted = $all->where('completed_count', 0)->count();
        $inactive = $all->where('completed_count', '>', 0)->count() - $activeRecently;

        $progressValues = $all->map(fn (Participant $participant) => $participant->progressPercent());

        return [
            'total' => $all->count(),
            'today' => $todayCount,
            'active' => $activeRecently,
            'inactive' => max(0, $inactive),
            'not_started' => $notStarted,
            'average' => $progressValues->isEmpty() ? 0 : (int) round($progressValues->average()),
        ];
    }

    /**
     * افزودن شرکت‌کننده‌ها به فهرست چالش (به صورت گروهی یا یکی‌یکی).
     */
    public function store(Request $request, Challenge $challenge): RedirectResponse
    {
        $validated = $request->validate([
            'names' => ['nullable', 'string', 'max:10000'],
            'name' => ['nullable', 'string', 'max:190'],
            'contact' => ['nullable', 'string', 'max:190'],
        ], [
            'name.max' => 'نام نمی‌تواند بیشتر از ۱۹۰ کاراکتر باشد.',
        ], [
            'names' => 'فهرست نام‌ها',
            'name' => 'نام',
            'contact' => 'شماره تماس',
        ]);

        $names = TextList::lines($validated['names'] ?? null);

        if ($names === [] && filled($validated['name'] ?? null)) {
            $names = [trim((string) $validated['name'])];
        }

        if ($names === []) {
            return back()->withErrors(['names' => 'نام‌ها را وارد کنید؛ هر خط یک نام.']);
        }

        $created = 0;
        $skipped = 0;

        foreach ($names as $index => $name) {
            $exists = $challenge->participants()
                ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            $challenge->participants()->create([
                'name' => $name,
                'contact' => $index === 0 ? ($validated['contact'] ?? null) : null,
            ]);

            $created++;
        }

        $message = fa_num($created).' نفر به فهرست چالش اضافه شد.';

        if ($skipped > 0) {
            $message .= ' '.fa_num($skipped).' نفر از قبل در فهرست بودند.';
        }

        return redirect()
            ->route('panel.challenges.show', $challenge)
            ->with('status', $message);
    }

    /**
     * صفحه وضعیت هر شرکت‌کننده برای مدیر.
     */
    public function show(Participant $participant): View
    {
        $participant->load(['challenge.tasks', 'challenge.questions', 'completions']);

        $challenge = $participant->challenge;
        $breakdown = $participant->dayBreakdown();
        $activity = $participant->activityByDay(14);

        return view('panel.participants.show', [
            'participant' => $participant,
            'challenge' => $challenge,
            'breakdown' => $breakdown,
            'questionCounts' => $challenge->questionsByDay()->map->count(),
            'answers' => $participant->questionAnswers()->with('question')->latest('updated_at')->get(),
            'activity' => $activity,
            'recent' => $participant->recentCompletions(10),
            'times' => $participant->completionTimes(),
            'stats' => [
                'completed' => $participant->completedCount(),
                'total' => $participant->totalTasks(),
                'progress' => $participant->progressPercent(),
                'streak' => $participant->currentStreak(),
                'best_streak' => $participant->bestStreak(),
                'missed_days' => $participant->missedDaysCount(),
                'completed_days' => $participant->completedDaysCount(),
                'days_count' => $challenge->daysCount(),
                'current_day' => $challenge->currentDayNumber(),
                'first_activity' => $participant->firstActivityAt(),
                'last_activity' => $participant->lastActivityAt(),
            ],
        ]);
    }

    public function update(Request $request, Participant $participant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'contact' => ['nullable', 'string', 'max:190'],
            'note' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.required' => 'نام شرکت‌کننده را وارد کنید.',
        ]);

        $participant->update($validated);

        return back()->with('status', 'اطلاعات شرکت‌کننده ذخیره شد.');
    }

    public function destroy(Participant $participant): RedirectResponse
    {
        $challenge = $participant->challenge;
        $name = $participant->name;

        $participant->delete();

        return redirect()
            ->route('panel.challenges.show', $challenge)
            ->with('status', '«'.$name.'» از فهرست چالش حذف شد.');
    }

    /**
     * ساخت لینک اختصاصی جدید (لینک قبلی از کار می‌افتد).
     */
    public function regenerateToken(Participant $participant): RedirectResponse
    {
        $participant->update(['token' => Participant::generateToken()]);

        return back()->with('status', 'لینک اختصاصی جدید ساخته شد. لینک قبلی دیگر باز نمی‌شود.');
    }

    public function toggleTask(Participant $participant, ChallengeTask $task): RedirectResponse
    {
        abort_unless((int) $task->challenge_id === (int) $participant->challenge_id, 404);

        $isDone = $participant->toggleTask($task);

        return back()->with('status', $isDone
            ? 'برای «'.$participant->name.'» تیک زده شد.'
            : 'تیک «'.$participant->name.'» برداشته شد.');
    }
}
