<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use App\Models\Visit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ChallengeController extends Controller
{
    public function index(): View
    {
        $challenges = Challenge::query()
            ->where('is_published', true)
            ->with(['tasks', 'questions', 'participants'])
            ->withCount(['tasks', 'questions', 'participants'])
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return view('challenges.index', [
            'challenges' => $challenges,
        ]);
    }

    public function show(Request $request, Challenge $challenge): View
    {
        abort_unless($challenge->is_published, 404);

        Visit::record($request, Visit::TYPE_CHALLENGE, $challenge);

        $challenge->load(['tasks', 'participants', 'tips', 'questions', 'attachments', 'dayFiles', 'days'])
            ->loadCount(['tasks', 'participants']);

        return view('challenges.show', [
            'challenge' => $challenge,
        ]);
    }

    /**
     * ثبت «دیده‌شدن» یک روز: وقتی شرکت‌کننده کارت روز را در صفحه چالش یا
     * داشبوردش باز می‌کند، یک beacon کوچک این‌جا می‌فرستد (برای آمار بازدید).
     */
    public function dayView(Request $request, Challenge $challenge): Response
    {
        abort_unless($challenge->is_published, 404);

        $validated = $request->validate([
            'day_number' => ['required', 'integer', 'min:1', 'max:365'],
        ]);

        abort_unless($challenge->isDayUnlocked((int) $validated['day_number']), 404);

        Visit::record($request, Visit::TYPE_DAY, $challenge, (int) $validated['day_number']);

        return response()->noContent();
    }
}
