@extends('layouts.panel')

@section('title', 'داشبورد مدیر کل')
@section('page-title', 'داشبورد مدیر کل')
@section('page-subtitle', 'مدیریت فایل‌های سایت، دیدن ارتباط داده‌ها و حجم مصرفی')

@section('content')
    @php
        $humanBytes = fn ($bytes) => \App\Http\Controllers\Panel\SuperDashboardController::humanBytes($bytes);
        $diskUsedPercent = $storage['disk_total'] > 0
            ? (int) round(100 - ($storage['disk_free'] / $storage['disk_total'] * 100))
            : 0;
    @endphp

    {{-- آمار سریع --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-stat-card label="حجم فایل‌ها" :value="$humanBytes($storage['total_bytes'])" :hint="fa_num($storage['total_files']).' فایل آپلودشده'" icon="💾" />
        <x-stat-card label="فایل‌های روزها" :value="fa_num($storage['audio']['total'] + $storage['images']['total'] + $storage['others']['total'])" :hint="$humanBytes($storage['audio']['bytes'] + $storage['images']['bytes'] + $storage['others']['bytes'])" icon="🗂️" tone="sky" />
        <x-stat-card label="ضمیمه‌های چالش‌ها" :value="fa_num($storage['attachments']['total'])" :hint="$humanBytes($storage['attachments']['bytes'])" icon="📎" tone="amber" />
        <x-stat-card label="فضای آزاد دیسک" :value="$humanBytes($storage['disk_free'])" :hint="fa_num($diskUsedPercent).'٪ دیسک پر شده'" icon="🧮" tone="emerald" />
        <x-stat-card label="مدیران" :value="fa_num($totals['admins'])" :hint="fa_num($totals['participants']).' شرکت‌کننده در سایت'" icon="🧑‍💼" tone="rose" />
    </div>

    {{-- حجم مصرفی --}}
    <section class="card space-y-4">
        <div>
            <h2 class="text-base">💾 حجم مصرفی</h2>
            <p class="mt-1 text-xs text-muted">جمع حجم فایل‌ها از پایگاه داده خوانده می‌شود؛ فضای دیسک از سرور پرسیده می‌شود.</p>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl bg-cream p-4">
                <p class="text-xs text-muted">🎧 فایل‌های صوتی روزها</p>
                <p class="mt-1 text-lg font-bold text-ink">{{ $humanBytes($storage['audio']['bytes']) }}</p>
                <p class="mt-0.5 text-[11px] text-hint">{{ fa_num($storage['audio']['total']) }} فایل</p>
            </div>

            <div class="rounded-2xl bg-cream p-4">
                <p class="text-xs text-muted">🖼 تصویرهای روزها</p>
                <p class="mt-1 text-lg font-bold text-ink">{{ $humanBytes($storage['images']['bytes']) }}</p>
                <p class="mt-0.5 text-[11px] text-hint">{{ fa_num($storage['images']['total']) }} فایل</p>
            </div>

            <div class="rounded-2xl bg-cream p-4">
                <p class="text-xs text-muted">📎 سایر فایل‌ها و ضمیمه‌ها</p>
                <p class="mt-1 text-lg font-bold text-ink">{{ $humanBytes($storage['others']['bytes'] + $storage['attachments']['bytes']) }}</p>
                <p class="mt-0.5 text-[11px] text-hint">{{ fa_num($storage['others']['total'] + $storage['attachments']['total']) }} فایل</p>
            </div>

            <div class="rounded-2xl bg-cream p-4">
                <p class="text-xs text-muted">🗄️ پایگاه داده</p>
                <p class="mt-1 text-lg font-bold text-ink">{{ $storage['db_bytes'] !== null ? $humanBytes($storage['db_bytes']) : '—' }}</p>
                <p class="mt-0.5 text-[11px] text-hint">{{ $storage['db_bytes'] !== null ? 'فایل SQLite' : 'در دسترس نیست' }}</p>
            </div>
        </div>

        <div class="rounded-2xl bg-paper p-4 ring-1 ring-line">
            <div class="flex flex-wrap items-center justify-between gap-2 text-xs">
                <p class="font-semibold text-ink">مجموع فایل‌های آپلودشده: {{ $humanBytes($storage['total_bytes']) }}</p>

                @if ($storage['disk_total'] > 0)
                    <p class="text-muted">
                        دیسک سرور: {{ $humanBytes($storage['disk_total'] - $storage['disk_free']) }} مصرف‌شده از {{ $humanBytes($storage['disk_total']) }}
                    </p>
                @endif
            </div>

            @if ($storage['disk_total'] > 0)
                <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-cream ring-1 ring-line" role="img" aria-label="{{ fa_num($diskUsedPercent) }} درصد دیسک پر شده">
                    <div class="h-full rounded-full bg-jade" style="width: {{ $diskUsedPercent }}%"></div>
                </div>
            @endif
        </div>
    </section>

    {{-- ارتباط‌ها --}}
    <section class="card space-y-4">
        <div>
            <h2 class="text-base">🔗 ارتباط‌ها</h2>
            <p class="mt-1 text-xs text-muted">هر چالش با چه چیزهایی گره خورده است — روزها، کارها، پرسش‌ها، نکته‌ها، فایل‌ها و شرکت‌کننده‌ها.</p>
        </div>

        @if ($challenges->isEmpty())
            <div class="rounded-2xl bg-cream p-8 text-center">
                <p class="text-2xl">🌱</p>
                <p class="mt-3 text-sm text-ink/80">هنوز چالشی ساخته نشده است.</p>
            </div>
        @else
            <div class="table-wrap">
                <table class="table table-cards">
                    <thead>
                        <tr>
                            <th>چالش</th>
                            <th>مدیر</th>
                            <th>روزها</th>
                            <th>کارها</th>
                            <th>پرسش‌ها</th>
                            <th>نکته‌ها</th>
                            <th>فایل روزها</th>
                            <th>ضمیمه‌ها</th>
                            <th>شرکت‌کننده‌ها</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($challenges as $challenge)
                            <tr>
                                <td data-label="چالش">
                                    <span class="font-semibold text-ink">{{ $challenge->title }}</span>
                                    <span class="block text-[11px] text-hint">{{ $challenge->is_published ? 'منتشرشده' : 'پیش‌نویس' }}</span>
                                </td>
                                <td data-label="مدیر">{{ $challenge->admin?->username ?? '—' }}</td>
                                <td data-label="روزها">{{ fa_num($challenge->days_count) }}</td>
                                <td data-label="کارها">{{ fa_num($challenge->tasks_count) }}</td>
                                <td data-label="پرسش‌ها">{{ fa_num($challenge->questions_count) }}</td>
                                <td data-label="نکته‌ها">{{ fa_num($challenge->tips_count) }}</td>
                                <td data-label="فایل روزها">
                                    {{ fa_num($challenge->day_files_count) }}
                                    <span class="block text-[11px] text-hint">{{ $humanBytes($challenge->day_files_sum_size) }}</span>
                                </td>
                                <td data-label="ضمیمه‌ها">
                                    {{ fa_num($challenge->attachments_count) }}
                                    <span class="block text-[11px] text-hint">{{ $humanBytes($challenge->attachments_sum_size) }}</span>
                                </td>
                                <td data-label="شرکت‌کننده‌ها">{{ fa_num($challenge->participants_count) }}</td>
                                <td data-label="">
                                    <a href="{{ route('panel.challenges.show', $challenge) }}" class="btn btn-white btn-sm">مدیریت</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="rounded-2xl bg-cream px-4 py-3 text-xs leading-6 text-muted">
                در مجموع:
                {{ fa_num($challenges->sum('tasks_count')) }} کار،
                {{ fa_num($challenges->sum('questions_count')) }} پرسش،
                {{ fa_num($totals['answers']) }} پاسخ ثبت‌شده،
                {{ fa_num($totals['completions']) }} تیک انجام،
                {{ fa_num($totals['participants']) }} شرکت‌کننده و
                {{ fa_num($totals['admins']) }} مدیر.
            </p>
        @endif
    </section>

    {{-- مدیریت فایل‌ها --}}
    <section class="card space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-base">🗂️ مدیریت فایل‌ها</h2>
                <p class="mt-1 text-xs text-muted">همه فایل‌هایی که به روزهای چالش‌ها پیوست شده‌اند، بزرگ‌ترین فایل اول.</p>
            </div>

            <form method="POST" action="{{ route('panel.super.files.cleanup') }}" data-confirm="رکوردهایی که فایلشان روی دیسک نیست حذف شوند؟">
                @csrf
                <button type="submit" class="btn btn-white btn-sm">🧹 پاکسازی فایل‌های گمشده</button>
            </form>
        </div>

        <form method="GET" action="{{ route('panel.super.dashboard') }}" class="flex flex-wrap items-end gap-3">
            <div class="min-w-52 flex-1">
                <label class="label" for="q">جست‌وجو در عنوان یا نام فایل</label>
                <input id="q" name="q" type="text" value="{{ $search }}" class="input" placeholder="مثلاً صوت روز اول">
            </div>

            <div>
                <label class="label" for="type">نوع فایل</label>
                <select id="type" name="type" class="input">
                    <option value="all" @selected($type === 'all')>همه</option>
                    <option value="audio" @selected($type === 'audio')>🎧 صوتی</option>
                    <option value="image" @selected($type === 'image')>🖼 تصویر</option>
                    <option value="other" @selected($type === 'other')>📎 سایر</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-sm">فیلتر کن</button>
        </form>

        @if ($dayFiles->isEmpty())
            <div class="rounded-2xl bg-cream p-8 text-center">
                <p class="text-2xl">🗂️</p>
                <p class="mt-3 text-sm text-ink/80">فایلی با این فیلترها پیدا نشد.</p>
            </div>
        @else
            <div class="table-wrap">
                <table class="table table-cards">
                    <thead>
                        <tr>
                            <th>عنوان</th>
                            <th>فایل</th>
                            <th>نوع</th>
                            <th>حجم</th>
                            <th>دانلودها</th>
                            <th>چالش / روز</th>
                            <th>وضعیت</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dayFiles as $file)
                            @php($exists = \Illuminate\Support\Facades\Storage::disk('local')->exists($file->path))
                            <tr>
                                <td data-label="عنوان" class="font-semibold text-ink">{{ $file->title }}</td>
                                <td data-label="فایل"><span class="text-[11px] text-hint">{{ $file->original_name }}</span></td>
                                <td data-label="نوع">
                                    <span class="badge {{ $file->is_audio ? 'bg-gold/15 text-gold-ink ring-gold/50' : ($file->isImage() ? 'bg-jade/10 text-jade ring-jade/30' : 'bg-leaf/10 text-forest ring-leaf/30') }}">
                                        {{ $file->is_audio ? '🎧 صوتی' : ($file->isImage() ? '🖼 تصویر' : '📎 فایل') }}
                                    </span>
                                </td>
                                <td data-label="حجم">{{ $humanBytes($file->size) }}</td>
                                <td data-label="دانلودها">{{ fa_num($file->downloads) }}</td>
                                <td data-label="چالش / روز">
                                    @if ($file->challenge)
                                        <a href="{{ route('panel.challenges.show', $file->challenge) }}" class="text-jade underline">
                                            {{ \Illuminate\Support\Str::limit($file->challenge->title, 24) }}
                                        </a>
                                        <span class="block text-[11px] text-hint">
                                            {{ $file->day_number ? 'روز '.fa_num($file->day_number) : 'بدون روز مشخص' }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td data-label="وضعیت">
                                    @if ($exists)
                                        <span class="badge bg-leaf/10 text-forest ring-leaf/30">✓ موجود</span>
                                    @else
                                        <span class="badge bg-danger-soft text-danger ring-danger/30">✗ گمشده</span>
                                    @endif
                                </td>
                                <td data-label="">
                                    <div class="flex items-center gap-2">
                                        @if ($exists)
                                            <a href="{{ $file->url() }}" target="_blank" rel="noopener" class="btn btn-white btn-sm" aria-label="باز کردن {{ $file->title }}">↗</a>
                                        @endif

                                        <form method="POST" action="{{ route('panel.super.files.destroy', ['type' => 'day-files', 'file' => $file->id]) }}" data-confirm="فایل «{{ $file->title }}» برای همیشه حذف شود؟">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $dayFiles->links() }}
        @endif

        @if ($attachments->isNotEmpty())
            <div class="border-t border-line pt-4">
                <h3 class="text-sm font-semibold text-forest">📎 ضمیمه‌های عمومی چالش‌ها</h3>

                <div class="table-wrap mt-3">
                    <table class="table table-cards">
                        <thead>
                            <tr>
                                <th>عنوان</th>
                                <th>فایل</th>
                                <th>حجم</th>
                                <th>دانلودها</th>
                                <th>چالش</th>
                                <th>وضعیت</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($attachments as $attachment)
                                @php($exists = \Illuminate\Support\Facades\Storage::disk('local')->exists($attachment->path))
                                <tr>
                                    <td data-label="عنوان" class="font-semibold text-ink">{{ $attachment->title }}</td>
                                    <td data-label="فایل"><span class="text-[11px] text-hint">{{ $attachment->original_name }}</span></td>
                                    <td data-label="حجم">{{ $humanBytes($attachment->size) }}</td>
                                    <td data-label="دانلودها">{{ fa_num($attachment->downloads) }}</td>
                                    <td data-label="چالش">
                                        @if ($attachment->challenge)
                                            <a href="{{ route('panel.challenges.show', $attachment->challenge) }}" class="text-jade underline">{{ \Illuminate\Support\Str::limit($attachment->challenge->title, 24) }}</a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td data-label="وضعیت">
                                        @if ($exists)
                                            <span class="badge bg-leaf/10 text-forest ring-leaf/30">✓ موجود</span>
                                        @else
                                            <span class="badge bg-danger-soft text-danger ring-danger/30">✗ گمشده</span>
                                        @endif
                                    </td>
                                    <td data-label="">
                                        <div class="flex items-center gap-2">
                                            @if ($exists)
                                                <a href="{{ $attachment->url() }}" target="_blank" rel="noopener" class="btn btn-white btn-sm" aria-label="باز کردن {{ $attachment->title }}">↗</a>
                                            @endif

                                            <form method="POST" action="{{ route('panel.super.files.destroy', ['type' => 'attachments', 'file' => $attachment->id]) }}" data-confirm="ضمیمه «{{ $attachment->title }}» برای همیشه حذف شود؟">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </section>
@endsection
