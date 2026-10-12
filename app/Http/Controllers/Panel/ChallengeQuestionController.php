<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Panel\Concerns\ResolvesChallengeDay;
use App\Models\Challenge;
use App\Models\ChallengeQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChallengeQuestionController extends Controller
{
    use ResolvesChallengeDay;

    public function store(Request $request, Challenge $challenge): RedirectResponse
    {
        $validated = $this->validated($request);
        $dayNumber = $this->resolveDayNumber($request, $challenge);

        $challenge->questions()->create([
            'day_number' => $dayNumber,
            'question' => $validated['question'],
            'answer' => $validated['answer'] ?? null,
            'asked_by' => $validated['asked_by'] ?? null,
            'is_published' => $request->boolean('is_published', true),
            'sort_order' => $this->nextSortOrder($challenge, $dayNumber),
        ]);

        return back()->with('status', $dayNumber
            ? 'پرسش برای روز '.fa_num($dayNumber).' اضافه شد.'
            : 'پرسش اضافه شد.');
    }

    public function update(Request $request, Challenge $challenge, ChallengeQuestion $question): RedirectResponse
    {
        $this->ensureBelongsToChallenge($challenge, $question);

        $validated = $this->validated($request);

        $question->update([
            'day_number' => $this->resolveDayNumber($request, $challenge),
            'question' => $validated['question'],
            'answer' => $validated['answer'] ?? null,
            'asked_by' => $validated['asked_by'] ?? null,
            'is_published' => $request->boolean('is_published'),
        ]);

        return back()->with('status', 'پرسش ویرایش شد.');
    }

    public function destroy(Challenge $challenge, ChallengeQuestion $question): RedirectResponse
    {
        $this->ensureBelongsToChallenge($challenge, $question);

        $question->delete();

        return back()->with('status', 'پرسش حذف شد.');
    }

    public function togglePublish(Challenge $challenge, ChallengeQuestion $question): RedirectResponse
    {
        $this->ensureBelongsToChallenge($challenge, $question);

        $question->update(['is_published' => ! $question->is_published]);

        return back()->with('status', $question->is_published
            ? 'پرسش در صفحه چالش نمایش داده می‌شود.'
            : 'پرسش از صفحه چالش پنهان شد.');
    }

    public function move(Challenge $challenge, ChallengeQuestion $question, string $direction): RedirectResponse
    {
        $this->ensureBelongsToChallenge($challenge, $question);

        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        $siblings = $challenge->questions()
            ->whereKeyNot($question->getKey())
            ->when(
                $question->day_number,
                fn ($query) => $query->where('day_number', $question->day_number),
                fn ($query) => $query->whereNull('day_number')
            )
            ->get();

        $neighbour = $direction === 'up'
            ? $siblings->where('sort_order', '<=', $question->sort_order)->last()
            : $siblings->where('sort_order', '>=', $question->sort_order)->first();

        if ($neighbour) {
            $order = $question->sort_order;
            $question->update(['sort_order' => $neighbour->sort_order]);
            $neighbour->update(['sort_order' => $order]);
        }

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'date' => ['nullable', 'string', 'max:16', $this->jalaliDateRule()],
            'day_number' => ['nullable', 'integer', 'min:1', 'max:365'],
            'question' => ['required', 'string', 'max:190'],
            'answer' => ['nullable', 'string', 'max:4000'],
            'asked_by' => ['nullable', 'string', 'max:120'],
        ], [
            'question.required' => 'متن پرسش را وارد کنید.',
        ], [
            'question' => 'پرسش',
            'answer' => 'پاسخ',
            'asked_by' => 'پرسنده',
        ]);
    }

    /**
     * ترتیب پرسش‌ها در همان روز (گروه) جداگانه شمرده می‌شود.
     */
    private function nextSortOrder(Challenge $challenge, ?int $dayNumber): int
    {
        return (int) $challenge->questions()
            ->when(
                $dayNumber,
                fn ($query) => $query->where('day_number', $dayNumber),
                fn ($query) => $query->whereNull('day_number')
            )
            ->max('sort_order') + 1;
    }

    private function ensureBelongsToChallenge(Challenge $challenge, ChallengeQuestion $question): void
    {
        abort_unless((int) $question->challenge_id === (int) $challenge->id, 404);
    }
}
