<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\ChallengeQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChallengeQuestionController extends Controller
{
    public function store(Request $request, Challenge $challenge): RedirectResponse
    {
        $validated = $this->validated($request);

        $challenge->questions()->create([
            'question' => $validated['question'],
            'answer' => $validated['answer'] ?? null,
            'asked_by' => $validated['asked_by'] ?? null,
            'is_published' => $request->boolean('is_published', true),
            'sort_order' => (int) $challenge->questions()->max('sort_order') + 1,
        ]);

        return back()->with('status', 'پرسش اضافه شد.');
    }

    public function update(Request $request, Challenge $challenge, ChallengeQuestion $question): RedirectResponse
    {
        $this->ensureBelongsToChallenge($challenge, $question);

        $validated = $this->validated($request);

        $question->update([
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

        $siblings = $challenge->questions()->whereKeyNot($question->getKey())->get();

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

    private function ensureBelongsToChallenge(Challenge $challenge, ChallengeQuestion $question): void
    {
        abort_unless((int) $question->challenge_id === (int) $challenge->id, 404);
    }
}
