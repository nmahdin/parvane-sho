<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\ChallengeTask;
use App\Models\Participant;
use App\Support\TextList;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ParticipantController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $challengeFilter = $request->query('challenge');

        $participants = Participant::query()
            ->with(['challenge.tasks', 'completions'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->when($challengeFilter, fn ($query) => $query->whereHas(
                'challenge',
                fn ($q) => $q->where('slug', $challengeFilter)->orWhere('id', $challengeFilter)
            ))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('panel.participants.index', [
            'participants' => $participants,
            'challenges' => Challenge::query()->orderBy('title')->get(),
            'search' => $search,
            'challengeFilter' => $challengeFilter,
        ]);
    }

    /**
     * افزودن شرکت‌کننده‌ها به فهرست چالش (به صورت گروهی یا یکی‌یکی).
     */
    public function store(Request $request, Challenge $challenge): RedirectResponse
    {
        $validated = $request->validate([
            'names' => ['nullable', 'string', 'max:10000'],
            'name' => ['nullable', 'string', 'max:190'],
            'contact' => ['nullable', 'string', 'max:190'],
        ], [
            'name.max' => 'نام نمی‌تواند بیشتر از ۱۹۰ کاراکتر باشد.',
        ], [
            'names' => 'فهرست نام‌ها',
            'name' => 'نام',
            'contact' => 'شماره تماس',
        ]);

        $names = TextList::lines($validated['names'] ?? null);

        if ($names === [] && filled($validated['name'] ?? null)) {
            $names = [trim((string) $validated['name'])];
        }

        if ($names === []) {
            return back()->withErrors(['names' => 'نام‌ها را وارد کنید؛ هر خط یک نام.']);
        }

        $created = 0;
        $skipped = 0;

        foreach ($names as $index => $name) {
            $exists = $challenge->participants()
                ->whereRaw('lower(name) = ?', [mb_strtolower($name)])
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            $challenge->participants()->create([
                'name' => $name,
                'contact' => $index === 0 ? ($validated['contact'] ?? null) : null,
            ]);

            $created++;
        }

        $message = fa_num($created).' نفر به فهرست چالش اضافه شد.';

        if ($skipped > 0) {
            $message .= ' '.fa_num($skipped).' نفر از قبل در فهرست بودند.';
        }

        return redirect()
            ->route('panel.challenges.show', $challenge)
            ->with('status', $message)
            ->with('added_participants', true);
    }

    public function show(Participant $participant): View
    {
        $participant->load(['challenge.tasks']);

        return view('panel.participants.show', [
            'participant' => $participant,
            'challenge' => $participant->challenge,
        ]);
    }

    public function update(Request $request, Participant $participant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:190'],
            'contact' => ['nullable', 'string', 'max:190'],
            'note' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.required' => 'نام شرکت‌کننده را وارد کنید.',
        ]);

        $participant->update($validated);

        return back()->with('status', 'اطلاعات شرکت‌کننده ذخیره شد.');
    }

    public function destroy(Participant $participant): RedirectResponse
    {
        $challenge = $participant->challenge;
        $name = $participant->name;

        $participant->delete();

        return redirect()
            ->route('panel.challenges.show', $challenge)
            ->with('status', '«'.$name.'» از فهرست چالش حذف شد.');
    }

    /**
     * ساخت لینک اختصاصی جدید (لینک قبلی از کار می‌افتد).
     */
    public function regenerateToken(Participant $participant): RedirectResponse
    {
        $participant->update(['token' => Participant::generateToken()]);

        return back()->with('status', 'لینک اختصاصی جدید ساخته شد. لینک قبلی دیگر باز نمی‌شود.');
    }

    public function toggleTask(Participant $participant, ChallengeTask $task): RedirectResponse
    {
        abort_unless((int) $task->challenge_id === (int) $participant->challenge_id, 404);

        $isDone = $participant->toggleTask($task);

        return back()->with('status', $isDone
            ? 'برای «'.$participant->name.'» تیک زده شد.'
            : 'تیک «'.$participant->name.'» برداشته شد.');
    }
}
