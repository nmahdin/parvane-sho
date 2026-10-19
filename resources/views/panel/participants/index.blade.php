@extends('layouts.panel')

@section('title', 'شرکت‌کننده‌ها')
@section('page-title', 'مدیریت شرکت‌کننده‌ها')
@section('page-subtitle', 'وضعیت، پیشرفت و لینک اختصاصی همه شرکت‌کننده‌ها')

@section('page-actions')
    <a href="{{ route('panel.challenges.index') }}" class="btn btn-white btn-sm">مدیریت چالش‌ها</a>
@endsection

@section('content')
    {{-- آمار شرکت‌کننده‌ها --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-stat-card label="کل شرکت‌کننده‌ها" :value="fa_num($stats['total'])" hint="در همه چالش‌ها" icon="👥" />
        <x-stat-card label="امروز تیک زده‌اند" :value="fa_num($stats['today'])" hint="{{ fa_date(now(), true) }}" icon="✅" tone="emerald" />
        <x-stat-card label="۳ روز اخیر فعال" :value="fa_num($stats['active'])" hint="در حال ادامه دادن" icon="🔥" tone="sky" />
        <x-stat-card label="بی‌فعال" :value="fa_num($stats['inactive'])" hint="بیش از ۳ روز بی‌خبر" icon="😴" tone="amber" />
        <x-stat-card label="میانگین پیشرفت" :value="fa_num($stats['average']).'٪'" :hint="fa_num($stats['not_started']).' نفر هنوز شروع نکرده‌اند'" icon="📈" />
    </div>

    {{-- فیلترها --}}
    <section class="card">
        <form method="GET" action="{{ route('panel.participants.index') }}" class="grid gap-4 lg:grid-cols-[1.2fr_1fr_1fr_1fr_auto]">
            <div>
                <label class="label" for="q">جست‌وجوی نام</label>
                <input id="q" name="q" type="text" value="{{ $search }}" class="input" placeholder="بخشی از نام...">
            </div>

            <div>
                <label class="label" for="challenge">چالش</label>
                <select id="challenge" name="challenge" class="input">
                    <option value="">همه چالش‌ها</option>
                    @foreach ($challenges as $challengeOption)
                        <option value="{{ $challengeOption->slug }}" @selected($challengeFilter === $challengeOption->slug)>{{ $challengeOption->title }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label" for="status">وضعیت</label>
                <select id="status" name="status" class="input">
                    @foreach (\App\Http\Controllers\Panel\ParticipantController::FILTERS as $value => $label)
                        <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="label" for="sort">مرتب‌سازی</label>
                <select id="sort" name="sort" class="input">
                    @foreach (\App\Http\Controllers\Panel\ParticipantController::SORTS as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="btn btn-primary">اعمال</button>
                <a href="{{ route('panel.participants.index') }}" class="btn btn-white">پاک کردن</a>
            </div>
        </form>
    </section>

    {{-- فهرست --}}
    <section class="card">
        @if ($participants->isEmpty())
            <div class="py-8 text-center">
                <p class="text-2xl">🔍</p>
                <p class="mt-3 text-sm text-ink/80">شرکت‌کننده‌ای با این مشخصات پیدا نشد.</p>
            </div>
        @else
            <div class="flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-muted">{{ fa_num($participants->total()) }} نفر پیدا شد — صفحه {{ fa_num($participants->currentPage()) }} از {{ fa_num($participants->lastPage()) }}</p>
                <button type="button" class="btn btn-white btn-sm" data-copy-target="#all-participant-links">کپی لینک همه</button>
            </div>

            <div class="table-wrap mt-4">
                <table class="table table-cards">
                    <thead>
                        <tr>
                            <th>نام</th>
                            <th>چالش</th>
                            <th class="w-36">پیشرفت</th>
                            <th>وضعیت</th>
                            <th>آخرین فعالیت</th>
                            <th>آخرین بازدید</th>
                            <th class="min-w-[18rem]">لینک اختصاصی</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($participants as $participant)
                            @php
                                $statusInfo = $participant->activityStatus();
                                $progress = $participant->progressPercent();
                            @endphp

                            <tr>
                                <td data-label="">
                                    <a href="{{ route('panel.participants.show', $participant) }}" class="flex items-center gap-3">
                                        <span class="grid size-9 place-items-center rounded-xl bg-cream text-xs font-bold text-ink/80">{{ mb_substr($participant->name, 0, 1) }}</span>
                                        <span>
                                            <span class="block font-medium text-ink hover:text-brand-700">{{ $participant->name }}</span>
                                            @if ($participant->contact)
                                                <span class="text-[11px] text-hint">{{ $participant->contact }}</span>
                                            @endif

                                            @if ($participant->challenge->pledgeEnabled())
                                                <span class="mt-1 inline-block rounded-lg px-1.5 py-0.5 text-[10px] {{ $participant->hasPledged() ? 'bg-leaf/10 text-forest' : 'bg-gold/20 text-ink' }}">
                                                    {{ $participant->hasPledged() ? 'تعهدنامه ✓ ثبت شده' : 'تعهدنامه ✗ ثبت نشده' }}
                                                </span>
                                            @endif
                                        </span>
                                    </a>
                                </td>

                                <td data-label="چالش">
                                    <a href="{{ route('panel.challenges.show', $participant->challenge) }}" class="text-xs text-ink/80 hover:text-brand-700">
                                        <x-challenge-icon :value="$participant->challenge->emoji" size="size-4" class="text-forest" /> {{ $participant->challenge->title }}
                                    </a>
                                </td>

                                <td data-label="پیشرفت">
                                    <x-progress-bar :percent="$progress" :class="$participant->challenge->styleValue('bar')" show-label />
                                    <p class="mt-1.5 text-[11px] text-muted">{{ fa_num($participant->completedItems()) }} از {{ fa_num($participant->totalItems()) }} مورد</p>
                                </td>

                                <td data-label="وضعیت">
                                    <span class="badge {{ $statusInfo['classes'] }}">{{ $statusInfo['label'] }}</span>
                                </td>

                                <td data-label="آخرین فعالیت" class="text-xs text-muted">
                                    {{ $participant->last_completed_at ? fa_datetime($participant->last_completed_at) : '—' }}
                                </td>

                                <td data-label="آخرین بازدید" class="text-xs text-muted">
                                    {{ $participant->last_seen_at ? fa_datetime($participant->last_seen_at) : 'هنوز باز نکرده' }}
                                </td>

                                <td data-label="لینک اختصاصی">
                                    {{-- خود لینک نمایش داده نمی‌شود؛ فقط دکمهٔ کپی --}}
                                    <input id="row-link-{{ $participant->id }}" type="hidden" value="{{ route('participant.show', $participant) }}">
                                    <button type="button" class="btn btn-soft btn-sm" data-copy-target="#row-link-{{ $participant->id }}">کپی لینک</button>
                                </td>

                                <td data-label="">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('panel.participants.show', $participant) }}" class="btn btn-ghost btn-sm">وضعیت</a>
                                        <a href="{{ route('participant.show', $participant) }}" target="_blank" class="btn btn-ghost btn-sm">داشبورد ↗</a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <textarea id="all-participant-links" class="hidden" readonly>{{ $participants->getCollection()->map(fn ($p) => $p->name.' : '.route('participant.show', $p))->implode("\n") }}</textarea>

            <div class="mt-6">
                {{ $participants->links() }}
            </div>
        @endif
    </section>
@endsection
