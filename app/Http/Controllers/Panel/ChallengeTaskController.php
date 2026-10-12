<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\ChallengeTask;
use App\Support\TextList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChallengeTaskController extends Controller
{
    public function store(Request $request, Challenge $challenge): RedirectResponse
    {
        $validated = $this->validateTask($request);

        $challenge->tasks()->create([
            'day_number' => $validated['day_number'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'sort_order' => $this->nextSortOrder($challenge, $validated['day_number'] ?? null),
        ]);

        return back()->with('status', 'کار جدید اضافه شد.');
    }

    /**
     * افزودن گروهی کارها؛ هر خط یک کار است.
     */
    public function bulkStore(Request $request, Challenge $challenge): RedirectResponse
    {
        $validated = $request->validate([
            'day_number' => ['nullable', 'integer', 'min:1', 'max:365'],
            'items' => ['required', 'string', 'max:10000'],
        ], [
            'items.required' => 'هر خط یک کار بنویسید.',
        ]);

        $items = TextList::lines($validated['items']);

        if ($items === []) {
            return back()->withErrors(['items' => 'هیچ کاری پیدا نشد؛ هر خط یک کار بنویسید.']);
        }

        $day = $validated['day_number'] ?? null;
        $sortOrder = $this->nextSortOrder($challenge, $day);

        foreach ($items as $title) {
            $challenge->tasks()->create([
                'day_number' => $day,
                'title' => $title,
                'sort_order' => $sortOrder++,
            ]);
        }

        return back()->with('status', fa_num(count($items)).' کار اضافه شد.');
    }

    public function update(Request $request, Challenge $challenge, ChallengeTask $task): RedirectResponse
    {
        $this->ensureBelongsToChallenge($challenge, $task);

        $validated = $this->validateTask($request);

        $task->update([
            'day_number' => $validated['day_number'] ?? null,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
        ]);

        return back()->with('status', 'کار ویرایش شد.');
    }

    public function destroy(Challenge $challenge, ChallengeTask $task): RedirectResponse
    {
        $this->ensureBelongsToChallenge($challenge, $task);

        $task->delete();

        return back()->with('status', 'کار حذف شد.');
    }

    /**
     * جابه‌جایی کار به بالا یا پایین در همان روز.
     */
    public function move(Challenge $challenge, ChallengeTask $task, string $direction): RedirectResponse
    {
        $this->ensureBelongsToChallenge($challenge, $task);

        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        $siblings = $challenge->tasks()
            ->where('id', '!=', $task->id)
            ->when(
                $task->day_number,
                fn ($query) => $query->where('day_number', $task->day_number),
                fn ($query) => $query->whereNull('day_number')
            )
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $neighbour = $direction === 'up'
            ? $siblings->where('sort_order', '<=', $task->sort_order)->last()
            : $siblings->where('sort_order', '>=', $task->sort_order)->first();

        if (! $neighbour) {
            return back();
        }

        $taskOrder = $task->sort_order;
        $task->update(['sort_order' => $neighbour->sort_order]);
        $neighbour->update(['sort_order' => $taskOrder]);

        return back();
    }

    /**
     * @return array<string, mixed>
     */
    private function validateTask(Request $request): array
    {
        return $request->validate([
            'day_number' => ['nullable', 'integer', 'min:1', 'max:365'],
            'title' => ['required', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
        ], [
            'title.required' => 'عنوان کار را وارد کنید.',
            'day_number.integer' => 'شماره روز باید عدد باشد.',
        ]);
    }

    private function nextSortOrder(Challenge $challenge, ?int $dayNumber): int
    {
        return (int) $challenge->tasks()
            ->when(
                $dayNumber,
                fn ($query) => $query->where('day_number', $dayNumber),
                fn ($query) => $query->whereNull('day_number')
            )
            ->max('sort_order') + 1;
    }

    private function ensureBelongsToChallenge(Challenge $challenge, ChallengeTask $task): void
    {
        abort_unless((int) $task->challenge_id === (int) $challenge->id, 404);
    }
}
