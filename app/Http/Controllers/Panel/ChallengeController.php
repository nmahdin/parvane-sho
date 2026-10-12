<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Challenge;
use App\Models\ChallengeQuestion;
use App\Models\ChallengeTask;
use App\Models\ChallengeTip;
use App\Models\Participant;
use App\Support\Jalali;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ChallengeController extends Controller
{
    public function index(): View
    {
        $admin = $this->currentAdmin();

        $challenges = Challenge::query()
            ->visibleToAdmin($admin)
            ->with(['participants', 'tasks', 'questions', 'admin'])
            ->withCount(['tasks', 'questions', 'participants'])
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return view('panel.challenges.index', [
            'challenges' => $challenges,
            'admin' => $admin,
        ]);
    }

    public function create(): View
    {
        $admin = $this->currentAdmin();

        return view('panel.challenges.form', [
            'challenge' => new Challenge([
                'admin_id' => $admin?->getKey(),
                'emoji' => '🦋',
                'accent' => 'jade',
                'is_published' => true,
                'starts_on' => today(),
            ]),
            'adminOptions' => $this->ownerOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $admin = $this->currentAdmin();

        [$data] = $this->validated($request);

        $data['slug'] = Challenge::uniqueSlug($data['slug'] ?: $data['title']);
        $data['sort_order'] = $data['sort_order'] ?? ((int) Challenge::query()->max('sort_order') + 1);
        $data['admin_id'] = $this->resolveOwnerId($request, $admin);

        $challenge = Challenge::create($data);

        return redirect()
            ->route('panel.challenges.show', $challenge)
            ->with('status', 'چالش ساخته شد. حالا روزها، کارها و شرکت‌کننده‌ها را اضافه کنید.');
    }

    public function show(Challenge $challenge): View
    {
        $challenge->load(['tasks', 'tips', 'days', 'questions.answers.participant', 'attachments']);

        $participants = $challenge->participants()
            ->with(['completions', 'questionAnswers'])
            ->withCount(['completions as completed_count' => fn ($query) => $query->whereNotNull('completed_at')])
            ->get()
            // همان نمونه چالشِ بارگذاری‌شده را به شرکت‌کننده‌ها می‌دهیم تا
            // شمردن موارد و پیشرفت، کوئری اضافه نزند.
            ->each(fn (Participant $participant) => $participant->setRelation('challenge', $challenge));

        // وضعیت هر کار: چه کسانی انجام داده‌اند و چه کسانی نداده‌اند.
        $taskStatuses = $challenge->tasks
            ->mapWithKeys(fn (ChallengeTask $task) => [$task->getKey() => $task->statusForParticipants($participants)])
            ->all();

        $lastDay = (int) ($challenge->contentDayNumbers()->last() ?? 0);
        $nextDayNumber = $lastDay > 0 ? min(365, $lastDay + 1) : 1;

        // پیشنهاد پیش‌فرض تاریخ برای افزودن کار/پرسش: روز جاری چالش و اگر شروع نشده، روز بعدی خالی.
        $defaultDay = $challenge->currentDayNumber() ?? $nextDayNumber;

        return view('panel.challenges.show', [
            'challenge' => $challenge,
            'participants' => $participants,
            'taskStatuses' => $taskStatuses,
            'tasksCount' => $challenge->tasks->count(),
            'questionsCount' => $challenge->questions->count(),
            'days' => $this->dayBoxes($challenge),
            'nextDayNumber' => $nextDayNumber,
            'defaultDay' => $defaultDay,
            'defaultDate' => Jalali::inputValue($challenge->dateForDay($defaultDay)),
            'defaultDayLabel' => $challenge->dayDateLabel($defaultDay),
            'dayChoices' => $challenge->selectableDays()->map(fn (int $day) => [
                'day' => $day,
                'input' => Jalali::inputValue($challenge->dateForDay($day)),
                'label' => fa_date($challenge->dateForDay($day)),
                'is_past' => $challenge->dateForDay($day)->lt(today()),
                'is_today' => $challenge->dateForDay($day)->isToday(),
                'is_locked' => ! $challenge->isDayUnlocked($day),
                'count' => $challenge->dayCounts($day)['total'],
            ]),
        ]);
    }

    /**
     * «باکس روزها»: برای هر روز از چالش (بر اساس تاریخ شروع و پایان) یک باکس
     * با تاریخ، وضعیت باز/بسته، عنوان و توضیح و کارها/پرسش‌ها/نکته‌های همان روز.
     * روزهای چالش پیش‌فرض فعال‌اند، حتی اگر هنوز محتوایی نداشته باشند.
     *
     * @return list<array<string, mixed>>
     */
    private function dayBoxes(Challenge $challenge): array
    {
        $tasks = $challenge->tasksByDay();
        $questions = $challenge->questionsByDay();
        $tips = $challenge->tipsByDay();
        $settings = $challenge->days->keyBy('day_number');

        $boxes = [];

        // گروه «بدون روز مشخص» همیشه اول فهرست می‌آید تا با داشبورد
        // شرکت‌کننده و صفحه چالش یکی باشد (روز ۰ = موارد بدون روز).
        if ($tasks->has(0) || $questions->has(0) || $tips->has(0)) {
            $boxes[] = $this->dayBox(
                challenge: $challenge,
                dayNumber: 0,
                tasks: $tasks->get(0) ?? collect(),
                questions: $questions->get(0) ?? collect(),
                tips: $tips->get(0) ?? collect(),
            );
        }

        foreach ($challenge->dayBoxNumbers() as $dayNumber) {
            $boxes[] = $this->dayBox(
                challenge: $challenge,
                dayNumber: $dayNumber,
                tasks: $tasks->get($dayNumber) ?? collect(),
                questions: $questions->get($dayNumber) ?? collect(),
                tips: $tips->get($dayNumber) ?? collect(),
                title: $settings->get($dayNumber)?->title,
                description: $settings->get($dayNumber)?->description,
            );
        }

        // پیش‌فرض: باکس امروز باز باشد؛ اگر چالش شروع نشده، اولین روزی که محتوا دارد.
        $hasToday = collect($boxes)->contains(fn (array $box) => $box['is_today']);
        $firstWithContent = null;
        $firstEmpty = null;

        foreach ($boxes as $index => $box) {
            if ($firstWithContent === null && $box['has_content']) {
                $firstWithContent = $index;
            }

            if ($firstEmpty === null && ! $box['is_general'] && ! $box['has_content']) {
                $firstEmpty = $index;
            }
        }

        foreach ($boxes as $index => $box) {
            $boxes[$index]['open'] = $box['is_today']
                || (! $hasToday && $index === ($firstWithContent ?? $firstEmpty ?? 0));
        }

        return $boxes;
    }

    /**
     * ساخت داده‌های یک باکس روز.
     *
     * @param  Collection<int, ChallengeTask>  $tasks
     * @param  Collection<int, ChallengeQuestion>  $questions
     * @param  Collection<int, ChallengeTip>  $tips
     * @return array<string, mixed>
     */
    private function dayBox(
        Challenge $challenge,
        int $dayNumber,
        Collection $tasks,
        Collection $questions,
        Collection $tips,
        ?string $title = null,
        ?string $description = null,
    ): array {
        $isGeneral = $dayNumber < 1;

        return [
            'number' => $dayNumber,
            'is_general' => $isGeneral,
            'date_label' => $isGeneral ? null : $challenge->dayDateLabel($dayNumber),
            'date' => $isGeneral ? null : $challenge->dateForDay($dayNumber),
            'unlocked' => $isGeneral || $challenge->isDayUnlocked($dayNumber),
            'is_today' => ! $isGeneral && $challenge->currentDayNumber() === $dayNumber,
            'title' => $title,
            'description' => $description,
            'tasks' => $tasks,
            'questions' => $questions,
            'tips' => $tips,
            'counts' => [
                'tasks' => $tasks->count(),
                'questions' => $questions->count(),
                'tips' => $tips->count(),
                'total' => $tasks->count() + $questions->count(),
            ],
            'has_content' => $tasks->isNotEmpty()
                || $questions->isNotEmpty()
                || $tips->isNotEmpty()
                || filled($title)
                || filled($description),
        ];
    }

    public function edit(Challenge $challenge): View
    {
        return view('panel.challenges.form', [
            'challenge' => $challenge,
            'adminOptions' => $this->ownerOptions(),
        ]);
    }

    public function update(Request $request, Challenge $challenge): RedirectResponse
    {
        [$data] = $this->validated($request);

        $ownerId = $this->resolveOwnerId($request, $this->currentAdmin(), $challenge);

        if ($ownerId) {
            $data['admin_id'] = $ownerId;
        }

        $data['slug'] = $data['slug'] && $data['slug'] !== $challenge->slug
            ? Challenge::uniqueSlug($data['slug'], $challenge->id)
            : $challenge->slug;

        if ($data['sort_order'] === null) {
            unset($data['sort_order']);
        }

        $challenge->update($data);

        return redirect()
            ->route('panel.challenges.show', $challenge)
            ->with('status', 'تغییرات چالش ذخیره شد.');
    }

    public function destroy(Challenge $challenge): RedirectResponse
    {
        $title = $challenge->title;
        $challenge->delete();

        return redirect()
            ->route('panel.challenges.index')
            ->with('status', 'چالش «'.$title.'» و همه اطلاعات مربوط به آن حذف شد.');
    }

    public function togglePublish(Challenge $challenge): RedirectResponse
    {
        $challenge->update(['is_published' => ! $challenge->is_published]);

        return back()->with('status', $challenge->is_published
            ? 'چالش در سایت نمایش داده می‌شود.'
            : 'چالش از سایت پنهان شد.');
    }

    /**
     * اعتبارسنجی و آماده‌سازی داده‌های چالش (شامل تبدیل تاریخ شمسی ورودی‌ها).
     *
     * @return array{0: array<string, mixed>}
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9\-]+$/'],
            'tagline' => ['nullable', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:5000'],
            'long_description' => ['nullable', 'string', 'max:20000'],
            'contact_note' => ['nullable', 'string', 'max:190'],
            'emoji' => ['nullable', 'string', 'max:12'],
            'accent' => ['nullable', Rule::in(array_keys(Challenge::ACCENTS))],
            'starts_on' => ['nullable', 'string', 'max:16', $this->jalaliDateRule()],
            'ends_on' => ['nullable', 'string', 'max:16', $this->jalaliDateRule()],
            'pledge_title' => ['nullable', 'string', 'max:190'],
            'pledge_text' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ], [
            'title.required' => 'عنوان چالش را وارد کنید.',
            'slug.regex' => 'آدرس انگلیسی فقط می‌تواند شامل حروف کوچک لاتین، عدد و خط تیره باشد.',
            'long_description.max' => 'متن توضیحات بلند خیلی زیاد است.',
            'sort_order.integer' => 'ترتیب نمایش باید عدد باشد.',
        ]);

        $startsOn = Jalali::parseInput($validated['starts_on'] ?? null);
        $endsOn = Jalali::parseInput($validated['ends_on'] ?? null);

        if ($startsOn && $endsOn && $endsOn->lt($startsOn)) {
            throw ValidationException::withMessages([
                'ends_on' => 'تاریخ پایان نمی‌تواند قبل از تاریخ شروع باشد.',
            ]);
        }

        return [[
            'title' => $validated['title'],
            'slug' => $validated['slug'] ?? '',
            'tagline' => $validated['tagline'] ?? null,
            'description' => $validated['description'] ?? null,
            'long_description' => $validated['long_description'] ?? null,
            'contact_note' => $validated['contact_note'] ?? null,
            'emoji' => ($validated['emoji'] ?? null) ?: '🦋',
            'accent' => ($validated['accent'] ?? null) ?: 'violet',
            'starts_on' => $startsOn,
            'ends_on' => $endsOn,
            'is_published' => $request->boolean('is_published'),
            'daily_release' => $request->boolean('daily_release'),
            'pledge_enabled' => $request->boolean('pledge_enabled'),
            'pledge_title' => ($validated['pledge_title'] ?? null) ?: null,
            'pledge_text' => ($validated['pledge_text'] ?? null) ?: null,
            'sort_order' => isset($validated['sort_order']) ? (int) $validated['sort_order'] : null,
        ]];
    }

    private function currentAdmin(): ?Admin
    {
        return Auth::guard('admin')->user();
    }

    /**
     * فهرست مدیران برای انتخاب «مدیر چالش» — فقط مدیر اصلی می‌تواند چالش را
     * به مدیر دیگری بسپارد (برای مدیر چالش، فهرست خالی است و فیلدی هم نشان
     * داده نمی‌شود).
     *
     * @return Collection<int, Admin>
     */
    private function ownerOptions(): Collection
    {
        $admin = $this->currentAdmin();

        if (! $admin?->isSuper()) {
            return collect();
        }

        return Admin::query()->orderByDesc('is_super')->orderBy('name')->get();
    }

    /**
     * مدیر چالش انتخاب‌شده؛ مدیر چالش عادی همیشه مالک چالش‌های خودش است.
     */
    private function resolveOwnerId(Request $request, ?Admin $admin, ?Challenge $challenge = null): ?int
    {
        if (! $admin) {
            return null;
        }

        if (! $admin->isSuper()) {
            return $challenge?->admin_id ?? $admin->getKey();
        }

        $requested = $request->input('admin_id');

        if (filled($requested) && Admin::query()->whereKey((int) $requested)->exists()) {
            return (int) $requested;
        }

        return $challenge?->admin_id ?? $admin->getKey();
    }

    /**
     * قاعده اعتبارسنجی برای ورودی تاریخ شمسی.
     */
    private function jalaliDateRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (is_string($value) && trim($value) !== '' && Jalali::parseInput($value) === null) {
                $fail('قالب تاریخ درست نیست. نمونه: ۱۴۰۵/۰۷/۲۰');
            }
        };
    }
}
