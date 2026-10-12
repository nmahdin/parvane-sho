<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use Illuminate\Contracts\View\View;

class ChallengeController extends Controller
{
    public function index(): View
    {
        $challenges = Challenge::query()
            ->where('is_published', true)
            ->with(['tasks', 'participants'])
            ->withCount(['tasks', 'participants'])
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        return view('challenges.index', [
            'challenges' => $challenges,
        ]);
    }

    public function show(Challenge $challenge): View
    {
        abort_unless($challenge->is_published, 404);

        $challenge->load(['tasks', 'participants', 'tips', 'questions', 'attachments'])
            ->loadCount(['tasks', 'participants']);

        return view('challenges.show', [
            'challenge' => $challenge,
        ]);
    }
}
