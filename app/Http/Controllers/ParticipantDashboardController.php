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
        $participant->load([
            'challenge.tasks',
            'challenge.tips',
            'challenge.publishedQuestions',
            'challenge.attachments',
        ]);

        $participant->markSeen();

        return view('participant.show', [
            'participant' => $participant,
            'challenge' => $participant->challenge,
        ]);
    }

    /**
     * صفحه ورود شرکت‌کننده‌ها با کد اختصاصی (لینک کوتاه).
     */
    public function lookupForm(): View
    {
        return view('participant.lookup');
    }

    /**
     * ورود شرکت‌کننده با کد اختصاصی (لینک کوتاه) بدون نیاز به حساب کاربری.
     */
    public function lookup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:190'],
        ], [
            'code.required' => 'کد اختصاصی خود را وارد کنید.',
        ], [
            'code' => 'کد اختصاصی',
        ]);

        $code = trim((string) $validated['code']);

        // اگر کاربر کل لینک را جای کد بچسباند، کد را از انتهای نشانی برمی‌داریم
        if (str_contains($code, '/')) {
            $segments = array_values(array_filter(explode('/', (string) parse_url($code, PHP_URL_PATH))));
            $code = (string) end($segments);
        }

        $participant = Participant::query()
            ->where('token', mb_strtolower($code))
            ->first();

        if (! $participant) {
            return back()
                ->withInput()
                ->withErrors(['code' => 'با این کد شرکت‌کننده‌ای پیدا نشد. کد را از مسئول چالش بگیرید.']);
        }

        return redirect()->route('participant.show', $participant);
    }

    public function toggle(Request $request, Participant $participant, ChallengeTask $task): RedirectResponse
    {
        abort_unless((int) $task->challenge_id === (int) $participant->challenge_id, 404);

        // روزهای چالش روزبه‌روز باز می‌شوند؛ روزهای بعدی قفل هستند
        if ($participant->challenge->isTaskLocked($task)) {
            $unlock = $participant->challenge->unlockLabel($task->day_number);

            return redirect()
                ->to(route('participant.show', $participant).'#day-'.($task->day_number ?? 0))
                ->with('error', 'این روز هنوز باز نشده است'.($unlock ? '؛ از '.$unlock.' در دسترس قرار می‌گیرد' : '').'.');
        }

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
