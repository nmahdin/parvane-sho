<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\ChallengeAttachment;
use App\Models\DayFile;
use App\Models\Visit;
use App\Support\Jalali;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * آمار بازدید سایت: روزانه، ماهانه و تفکیک هر چالش +
 * پخش صوت‌ها، دانلود فایل‌ها و دیده‌شدن روزها.
 */
class AnalyticsController extends Controller
{
    public function index(Request $request): View
    {
        $admin = Auth::guard('admin')->user();

        $challenges = Challenge::query()
            ->visibleToAdmin($admin)
            ->orderByDesc('id')
            ->get();

        $selected = null;

        if ($request->filled('challenge')) {
            $selected = $challenges->firstWhere('id', $request->integer('challenge'));
        }

        // دامنه بازدیدها: مدیر کل همه، مدیر عادی فقط چالش‌های خودش
        $scope = fn () => Visit::query()->visibleToAdmin($admin)
            ->when($selected, fn (Builder $q) => $q->where('challenge_id', $selected->id));

        $today = now()->startOfDay();

        $totals = [
            'today' => $scope()->where('visited_at', '>=', $today)->count(),
            'week' => $scope()->where('visited_at', '>=', now()->subDays(6)->startOfDay())->count(),
            'month' => $scope()->where('visited_at', '>=', now()->subDays(29)->startOfDay())->count(),
            'all' => $scope()->count(),
            'day_views' => $scope()->where('type', Visit::TYPE_DAY)->count(),
            'dashboard' => $scope()->where('type', Visit::TYPE_DASHBOARD)->count(),
        ];

        // پخش صوت و دانلود فایل‌ها (ستون‌های شمارشی روی خود فایل‌ها)
        $fileScope = fn () => DayFile::query()
            ->when($selected, fn (Builder $q) => $q->where('challenge_id', $selected->id))
            ->when(! $selected && ! $admin->isSuper(), fn (Builder $q) => $q->whereIn('challenge_id', $challenges->pluck('id')));

        $fileTotals = [
            'plays' => (int) (clone $fileScope())->sum('plays'),
            'downloads' => (int) (clone $fileScope())->sum('downloads'),
            'attachments' => (int) ChallengeAttachment::query()
                ->when($selected, fn (Builder $q) => $q->where('challenge_id', $selected->id))
                ->when(! $selected && ! $admin->isSuper(), fn (Builder $q) => $q->whereIn('challenge_id', $challenges->pluck('id')))
                ->sum('downloads'),
        ];

        // نمودار روزانه ۳۰ روز اخیر (تفکیک نوع)
        $dailyRows = $scope()
            ->where('visited_at', '>=', now()->subDays(29)->startOfDay())
            ->selectRaw('DATE(visited_at) as d, type, COUNT(*) as c')
            ->groupBy('d', 'type')
            ->get();

        $daily = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $daily[$date] = ['challenge' => 0, 'day' => 0, 'dashboard' => 0, 'home' => 0, 'total' => 0];
        }

        foreach ($dailyRows as $row) {
            if (! isset($daily[$row->d])) {
                continue;
            }

            $daily[$row->d][$row->type] = (int) $row->c;
            $daily[$row->d]['total'] += (int) $row->c;
        }

        $dailyMax = max(1, max(array_map(fn ($d) => $d['total'], $daily)));

        // جمع ماهانه (شمسی) از ۱۸۳ روز اخیر
        $monthRows = $scope()
            ->where('visited_at', '>=', now()->subDays(182)->startOfDay())
            ->selectRaw('DATE(visited_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->get();

        $monthly = [];

        foreach ($monthRows as $row) {
            [$jy, $jm] = Jalali::fromGregorian(
                (int) substr($row->d, 0, 4),
                (int) substr($row->d, 5, 2),
                (int) substr($row->d, 8, 2),
            );

            $key = $jy.'-'.str_pad((string) $jm, 2, '0', STR_PAD_LEFT);
            $monthly[$key]['label'] ??= Jalali::MONTHS[$jm].' '.Jalali::toPersianDigits($jy);
            $monthly[$key]['total'] = ($monthly[$key]['total'] ?? 0) + (int) $row->c;
        }

        $monthly = array_slice($monthly, -6, true);
        $monthlyMax = max(1, max(array_map(fn ($m) => $m['total'], $monthly) ?: [1]));

        // جدول هر چالش
        $visitCounts = Visit::query()
            ->visibleToAdmin($admin)
            ->whereNotNull('challenge_id')
            ->selectRaw('challenge_id, COUNT(*) as c')
            ->groupBy('challenge_id')
            ->pluck('c', 'challenge_id');

        $todayCounts = Visit::query()->visibleToAdmin($admin)->where('visited_at', '>=', $today)->whereNotNull('challenge_id')->selectRaw('challenge_id, COUNT(*) as c')->groupBy('challenge_id')->pluck('c', 'challenge_id');
        $monthCounts = Visit::query()->visibleToAdmin($admin)->where('visited_at', '>=', now()->subDays(29)->startOfDay())->whereNotNull('challenge_id')->selectRaw('challenge_id, COUNT(*) as c')->groupBy('challenge_id')->pluck('c', 'challenge_id');
        $dayCounts = Visit::query()->visibleToAdmin($admin)->where('type', Visit::TYPE_DAY)->whereNotNull('challenge_id')->selectRaw('challenge_id, COUNT(*) as c')->groupBy('challenge_id')->pluck('c', 'challenge_id');

        $playsByChallenge = DayFile::query()->selectRaw('challenge_id, SUM(plays) as p, SUM(downloads) as d')->groupBy('challenge_id')->get()->keyBy('challenge_id');

        $perChallenge = $challenges->map(function (Challenge $challenge) use ($visitCounts, $todayCounts, $monthCounts, $dayCounts, $playsByChallenge) {
            $files = $playsByChallenge->get($challenge->id);

            return [
                'id' => $challenge->id,
                'title' => $challenge->title,
                'slug' => $challenge->slug,
                'published' => $challenge->is_published,
                'all' => (int) ($visitCounts[$challenge->id] ?? 0),
                'today' => (int) ($todayCounts[$challenge->id] ?? 0),
                'month' => (int) ($monthCounts[$challenge->id] ?? 0),
                'days' => (int) ($dayCounts[$challenge->id] ?? 0),
                'plays' => (int) ($files->p ?? 0),
                'downloads' => (int) ($files->d ?? 0) + (int) $challenge->attachments()->sum('downloads'),
                'participants' => $challenge->participants()->count(),
            ];
        });

        // تفکیک روزبه‌روز وقتی یک چالش انتخاب شده
        $perDay = null;

        if ($selected) {
            $perDayVisits = Visit::query()
                ->where('challenge_id', $selected->id)
                ->where('type', Visit::TYPE_DAY)
                ->whereNotNull('day_number')
                ->selectRaw('day_number, COUNT(*) as c')
                ->groupBy('day_number')
                ->pluck('c', 'day_number');

            $perDayFiles = DayFile::query()
                ->where('challenge_id', $selected->id)
                ->selectRaw('day_number, SUM(plays) as p, SUM(downloads) as d')
                ->groupBy('day_number')
                ->get()
                ->keyBy('day_number');

            $perDay = collect();

            for ($day = 1; $day <= max(1, $selected->daysCount()); $day++) {
                $files = $perDayFiles->get($day);

                $perDay->push([
                    'day' => $day,
                    'views' => (int) ($perDayVisits[$day] ?? 0),
                    'plays' => (int) ($files->p ?? 0),
                    'downloads' => (int) ($files->d ?? 0),
                ]);
            }
        }

        // دادهٔ نمودارها (chart.js) — برچسب‌ها شمسی و با رقم فارسی
        $dailyLabels = [];
        $series = ['challenge' => [], 'day' => [], 'dashboard' => [], 'home' => []];
        $typeTotals = ['challenge' => 0, 'day' => 0, 'dashboard' => 0, 'home' => 0];

        foreach ($daily as $date => $row) {
            [$jy, $jm, $jd] = Jalali::fromGregorian(
                (int) substr($date, 0, 4),
                (int) substr($date, 5, 2),
                (int) substr($date, 8, 2),
            );

            $dailyLabels[] = Jalali::toPersianDigits($jd).' '.Jalali::MONTHS[$jm];

            foreach ($series as $key => $values) {
                $series[$key][] = $row[$key];
                $typeTotals[$key] += $row[$key];
            }
        }

        $topChallenges = $perChallenge
            ->sortByDesc('month')
            ->take(8)
            ->values();

        $charts = [
            'daily' => ['labels' => $dailyLabels] + $series,
            'monthly' => [
                'labels' => array_column($monthly, 'label'),
                'totals' => array_column($monthly, 'total'),
            ],
            'types' => [
                'labels' => ['صفحه چالش', 'کارت روز', 'داشبورد شرکت‌کننده', 'صفحه اصلی'],
                'totals' => [$typeTotals['challenge'], $typeTotals['day'], $typeTotals['dashboard'], $typeTotals['home']],
            ],
            'challenges' => [
                'labels' => $topChallenges->pluck('title')->all(),
                'totals' => $topChallenges->pluck('month')->all(),
            ],
        ];

        return view('panel.analytics', [
            'chartsJson' => json_encode($charts, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
            'challenges' => $challenges,
            'selected' => $selected,
            'totals' => $totals,
            'fileTotals' => $fileTotals,
            'daily' => $daily,
            'dailyMax' => $dailyMax,
            'monthly' => $monthly,
            'monthlyMax' => $monthlyMax,
            'perChallenge' => $perChallenge,
            'perDay' => $perDay,
            'isSuper' => $admin->isSuper(),
        ]);
    }
}
