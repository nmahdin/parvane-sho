<?php

namespace App\Http\Controllers;

use App\Models\ChallengeTask;
use App\Models\Participant;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * داشبورد اختصاصی هر شرکت‌کننده؛ با یک لینک یکتا و بدون نیاز به ورود.
 */
class ParticipantDashboardController extends Controller
{
    public function show(Participant $participant): View
    {
        $participant->load(['challenge.tasks']);
        $participant->markSeen();

        return view('participant.show', [
            'participant' => $participant,
            'challenge' => $participant->challenge,
        ]);
    }

    public function toggle(Request $request, Participant $participant, ChallengeTask $task): RedirectResponse
    {
        abort_unless((int) $task->challenge_id === (int) $participant->challenge_id, 404);

        $isDone = $participant->toggleTask($task);

        return redirect()
            ->to(route('participant.show', $participant).'#task-'.$task->id)
            ->with('status', $isDone ? 'آفرین! این کار انجام شد. 🎉' : 'تیک این کار برداشته شد.');
    }

    public function updateNote(Request $request, Participant $participant): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:2000'],
        ], [], ['note' => 'یادداشت']);

        $participant->update(['note' => $validated['note'] ?? null]);

        return redirect()
            ->to(route('participant.show', $participant).'#note')
            ->with('status', 'یادداشت شما ذخیره شد.');
    }
}
