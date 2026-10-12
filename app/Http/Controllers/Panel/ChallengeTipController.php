<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\ChallengeTip;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChallengeTipController extends Controller
{
    public function store(Request $request, Challenge $challenge): RedirectResponse
    {
        $validated = $this->validated($request);

        $challenge->tips()->create([
            'emoji' => $validated['emoji'] ?: '💡',
            'title' => $validated['title'],
            'body' => $validated['body'] ?? null,
            'sort_order' => (int) $challenge->tips()->max('sort_order') + 1,
        ]);

        return back()->with('status', 'نکته جدید اضافه شد.');
    }

    public function update(Request $request, Challenge $challenge, ChallengeTip $tip): RedirectResponse
    {
        $this->ensureBelongsToChallenge($challenge, $tip);

        $validated = $this->validated($request);

        $tip->update([
            'emoji' => $validated['emoji'] ?: '💡',
            'title' => $validated['title'],
            'body' => $validated['body'] ?? null,
        ]);

        return back()->with('status', 'نکته ویرایش شد.');
    }

    public function destroy(Challenge $challenge, ChallengeTip $tip): RedirectResponse
    {
        $this->ensureBelongsToChallenge($challenge, $tip);

        $tip->delete();

        return back()->with('status', 'نکته حذف شد.');
    }

    public function move(Challenge $challenge, ChallengeTip $tip, string $direction): RedirectResponse
    {
        $this->ensureBelongsToChallenge($challenge, $tip);

        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        $siblings = $challenge->tips()->whereKeyNot($tip->getKey())->get();

        $neighbour = $direction === 'up'
            ? $siblings->where('sort_order', '<=', $tip->sort_order)->last()
            : $siblings->where('sort_order', '>=', $tip->sort_order)->first();

        if ($neighbour) {
            $order = $tip->sort_order;
            $tip->update(['sort_order' => $neighbour->sort_order]);
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
            'emoji' => ['nullable', 'string', 'max:12'],
            'title' => ['required', 'string', 'max:190'],
            'body' => ['nullable', 'string', 'max:2000'],
        ], [
            'title.required' => 'عنوان نکته را وارد کنید.',
        ], [
            'emoji' => 'نماد',
            'title' => 'عنوان نکته',
            'body' => 'متن نکته',
        ]);
    }

    private function ensureBelongsToChallenge(Challenge $challenge, ChallengeTip $tip): void
    {
        abort_unless((int) $tip->challenge_id === (int) $challenge->id, 404);
    }
}
