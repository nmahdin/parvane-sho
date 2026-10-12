<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use App\Models\Participant;
use App\Models\TaskCompletion;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
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

        $stats = [
            'challenges' => $challenges->count(),
            'participants' => Participant::query()->count(),
            'done' => TaskCompletion::query()->whereNotNull('completed_at')->count(),
        ];

        return view('home', [
            'challenges' => $challenges,
            'featured' => $challenges->take(3),
            'stats' => $stats,
        ]);
    }
}
