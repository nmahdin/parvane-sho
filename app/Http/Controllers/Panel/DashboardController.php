<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\ChallengeQuestionAnswer;
use App\Models\Participant;
use App\Models\TaskCompletion;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $challenges = Challenge::query()
            ->with(['participants', 'tasks', 'questions'])
            ->withCount(['tasks', 'questions', 'participants'])
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();

        $participants = Participant::query()
            ->with(['challenge.tasks'])
            ->withCount(['completions as completed_count' => fn ($query) => $query->whereNotNull('completed_at')])
            ->orderByDesc('completed_count')
            ->orderBy('name')
            ->get();

        $recent = TaskCompletion::query()
            ->with(['participant.challenge', 'task'])
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->limit(8)
            ->get();

        $recentAnswers = ChallengeQuestionAnswer::query()
            ->with(['participant', 'question'])
            ->latest('updated_at')
            ->limit(6)
            ->get();

        $stats = [
            'answers' => ChallengeQuestionAnswer::query()->count(),
            'challenges' => $challenges->count(),
            'published' => $challenges->where('is_published', true)->count(),
            'participants' => $participants->count(),
            'today' => TaskCompletion::query()
                ->whereNotNull('completed_at')
                ->whereDate('completed_at', today())
                ->count(),
            'done' => TaskCompletion::query()->whereNotNull('completed_at')->count(),
        ];

        return view('panel.dashboard', [
            'challenges' => $challenges,
            'participants' => $participants,
            'topParticipants' => $participants->where('completed_count', '>', 0)->take(5),
            'recent' => $recent,
            'recentAnswers' => $recentAnswers,
            'stats' => $stats,
        ]);
    }
}
