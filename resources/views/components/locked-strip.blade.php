{{--
    روزهای قفل‌شده در یک نوار جمع می‌شوند:
    - هیچ تعدادی از کار/پرسش/نکته نوشته نمی‌شود
    - کلمه «قفل» فقط یک بار در کل این نوار می‌آید
--}}
@props(['challenge', 'days'])

@if ($days->isNotEmpty())
    @php
        $first = (int) $days->min();
        $last = (int) $days->max();
        $single = $first === $last;
        $unlockLabel = $challenge->unlockLabel($first) ?: $challenge->dayDateLabel($first);
    @endphp

    <div id="locked-days" class="rounded-3xl bg-cream px-4 py-3.5 surface sm:px-5">
        <div class="flex flex-wrap items-center gap-3">
            <span class="grid size-9 shrink-0 place-items-center rounded-xl bg-paper text-sm ring-1 ring-line">🔒</span>

            <span class="badge shrink-0 bg-gold/15 text-ink ring-gold/50">قفل</span>

            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold text-ink">
                    @if ($single)
                        این روز هنوز باز نشده است
                    @else
                        روزهای {{ fa_num($first) }} تا {{ fa_num($last) }} هنوز باز نشده‌اند
                    @endif
                </p>

                <p class="mt-0.5 text-[11px] leading-5 text-muted">
                    پرسش‌ها و چالش عملی {{ $single ? 'این روز' : 'این روزها' }} هنوز آزاد نشده‌اند؛
                    @if ($unlockLabel)
                        از {{ $unlockLabel }} یکی‌یکی باز می‌شوند.
                    @else
                        یکی‌یکی باز می‌شوند.
                    @endif
                </p>
            </div>

            <details class="w-full sm:w-auto">
                <summary class="strip-toggle cursor-pointer list-none">تاریخ روزها</summary>

                <ul class="mt-2 space-y-1 rounded-2xl bg-paper p-3 text-[11px] text-muted ring-1 ring-line">
                    @foreach ($days as $lockedDay)
                        <li class="flex items-center justify-between gap-3">
                            <span>روز {{ fa_num($lockedDay) }}</span>
                            <span class="text-hint">{{ $challenge->dayDateLabel((int) $lockedDay) }}</span>
                        </li>
                    @endforeach
                </ul>
            </details>
        </div>
    </div>
@endif
