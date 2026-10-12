<?php

namespace App\Http\Controllers;

use App\Models\Challenge;
use App\Models\Participant;
use App\Models\TaskCompletion;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;

class HomeController extends Controller
{
    public function index(): View
    {
        $challenges = Challenge::query()
            ->where('is_published', true)
            ->with(['tasks', 'questions', 'participants.questionAnswers'])
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
            'hero' => $this->heroPreview($challenges),
        ]);
    }

    /**
     * پیش‌نمایش داشبورد شرکت‌کننده در بالای صفحه اصلی — همه‌چیز از داده‌های
     * واقعی دیتابیس می‌آید (چالش، شرکت‌کننده، کارهای همان روز و پیشرفت او).
     *
     * @param  Collection<int, Challenge>  $challenges
     * @return array<string, mixed>|null
     */
    private function heroPreview($challenges): ?array
    {
        foreach ($challenges as $challenge) {
            $participant = $challenge->participants
                ->sortByDesc(fn (Participant $participant) => $participant->progressPercent())
                ->first();

            if (! $participant) {
                continue;
            }

            $participant->setRelation('challenge', $challenge);

            $dayNumber = $challenge->currentDayNumber() ?? 1;
            $dayTasks = $challenge->tasks
                ->where('day_number', $dayNumber)
                ->values();

            if ($dayTasks->isEmpty()) {
                $dayTasks = $challenge->tasks->take(3)->values();
            }

            return [
                'challenge' => $challenge,
                'participant' => $participant,
                'day_number' => $dayNumber,
                'tasks' => $dayTasks->take(4),
                'progress' => $participant->progressPercent(),
                'completed' => $participant->completedItems(),
                'total' => $participant->totalItems(),
                'streak' => $participant->currentStreak(),
                'answered' => $participant->answeredQuestionsCount(),
                'questions' => $participant->questionsCount(),
            ];
        }

        return null;
    }
}
