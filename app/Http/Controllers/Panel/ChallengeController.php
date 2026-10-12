<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Support\Jalali;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ChallengeController extends Controller
{
    public function index(): View
    {
        $challenges = Challenge::query()
            ->with(['participants', 'tasks'])
            ->withCount(['tasks', 'participants'])
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return view('panel.challenges.index', [
            'challenges' => $challenges,
        ]);
    }

    public function create(): View
    {
        return view('panel.challenges.form', [
            'challenge' => new Challenge([
                'emoji' => '🦋',
                'accent' => 'jade',
                'is_published' => true,
                'starts_on' => today(),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        [$data] = $this->validated($request);

        $data['slug'] = Challenge::uniqueSlug($data['slug'] ?: $data['title']);
        $data['sort_order'] = $data['sort_order'] ?? ((int) Challenge::query()->max('sort_order') + 1);

        $challenge = Challenge::create($data);

        return redirect()
            ->route('panel.challenges.show', $challenge)
            ->with('status', 'چالش ساخته شد. حالا روزها، کارها و شرکت‌کننده‌ها را اضافه کنید.');
    }

    public function show(Challenge $challenge): View
    {
        $challenge->load(['tasks', 'tips', 'questions', 'attachments']);

        $participants = $challenge->participants()
            ->withCount(['completions as completed_count' => fn ($query) => $query->whereNotNull('completed_at')])
            ->get();

        $lastDay = (int) ($challenge->contentDayNumbers()->last() ?? 0);
        $nextDayNumber = $lastDay > 0 ? min(365, $lastDay + 1) : 1;

        // پیشنهاد پیش‌فرض تاریخ برای افزودن کار/پرسش: روز جاری چالش و اگر شروع نشده، روز بعدی خالی.
        $defaultDay = $challenge->currentDayNumber() ?? $nextDayNumber;

        return view('panel.challenges.show', [
            'challenge' => $challenge,
            'participants' => $participants,
            'tasksCount' => $challenge->tasks->count(),
            'questionsCount' => $challenge->questions->count(),
            'nextDayNumber' => $nextDayNumber,
            'defaultDay' => $defaultDay,
            'defaultDate' => Jalali::inputValue($challenge->dateForDay($defaultDay)),
            'defaultDayLabel' => $challenge->dayDateLabel($defaultDay),
        ]);
    }

    public function edit(Challenge $challenge): View
    {
        return view('panel.challenges.form', [
            'challenge' => $challenge,
        ]);
    }

    public function update(Request $request, Challenge $challenge): RedirectResponse
    {
        [$data] = $this->validated($request);

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
            'sort_order' => isset($validated['sort_order']) ? (int) $validated['sort_order'] : null,
        ]];
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
