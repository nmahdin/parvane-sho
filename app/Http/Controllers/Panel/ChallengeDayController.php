<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Panel\Concerns\ResolvesChallengeDay;
use App\Models\Challenge;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * افزودن کار یا پرسش به یک روز مشخص از چالش (تاریخ‌محور).
 */
class ChallengeDayController extends Controller
{
    use ResolvesChallengeDay;

    public function store(Request $request, Challenge $challenge): RedirectResponse
    {
        $type = $request->input('type') === 'question' ? 'question' : 'task';

        $request->validate([
            'date' => $this->dateRules($challenge),
            'day_number' => ['nullable', 'integer', 'min:1', 'max:365'],
            'type' => ['nullable', 'in:task,question'],
            'title' => [$type === 'task' ? 'required' : 'nullable', 'string', 'max:190'],
            'description' => ['nullable', 'string', 'max:2000'],
            'question' => [$type === 'question' ? 'required' : 'nullable', 'string', 'max:190'],
            'answer' => ['nullable', 'string', 'max:4000'],
            'asked_by' => ['nullable', 'string', 'max:120'],
        ], [
            'title.required' => 'عنوان کار را وارد کنید.',
            'question.required' => 'متن پرسش را وارد کنید.',
        ], [
            'title' => 'عنوان کار',
            'description' => 'توضیح کار',
            'question' => 'متن پرسش',
            'answer' => 'پاسخ',
            'asked_by' => 'پرسنده',
            'date' => 'تاریخ روز',
        ]);

        $dayNumber = $this->resolveDayNumber($request, $challenge);

        if ($type === 'question') {
            $challenge->questions()->create([
                'day_number' => $dayNumber,
                'question' => (string) $request->input('question'),
                'answer' => $request->input('answer'),
                'asked_by' => $request->input('asked_by'),
                'is_published' => $request->boolean('is_published', true),
                'sort_order' => $this->nextQuestionOrder($challenge, $dayNumber),
            ]);

            return back()->with('status', $this->savedMessage('پرسش', $dayNumber));
        }

        $challenge->tasks()->create([
            'day_number' => $dayNumber,
            'title' => (string) $request->input('title'),
            'description' => $request->input('description'),
            'sort_order' => $this->nextTaskOrder($challenge, $dayNumber),
        ]);

        return back()->with('status', $this->savedMessage('کار', $dayNumber));
    }

    private function savedMessage(string $label, ?int $dayNumber): string
    {
        return $dayNumber
            ? $label.' برای روز '.fa_num($dayNumber).' اضافه شد.'
            : $label.' جدید اضافه شد.';
    }

    private function nextTaskOrder(Challenge $challenge, ?int $dayNumber): int
    {
        return (int) $challenge->tasks()
            ->when(
                $dayNumber,
                fn ($query) => $query->where('day_number', $dayNumber),
                fn ($query) => $query->whereNull('day_number')
            )
            ->max('sort_order') + 1;
    }

    private function nextQuestionOrder(Challenge $challenge, ?int $dayNumber): int
    {
        return (int) $challenge->questions()
            ->when(
                $dayNumber,
                fn ($query) => $query->where('day_number', $dayNumber),
                fn ($query) => $query->whereNull('day_number')
            )
            ->max('sort_order') + 1;
    }
}
