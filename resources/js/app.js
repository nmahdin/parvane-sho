import { initJalaliPickers, jalaliInputToDate, toPersianDigits } from './jalali-datepicker';

/**
 * اسکریپت‌های سبک سایت: انتخابگر تاریخ شمسی، کپی لینک، منوی موبایل و تأیید حذف.
 */
const app = {
    init() {
        this.initAjaxSave();
        this.initUploadProgress();
        this.initModals();
        this.initDayViewTracking();
        this.initAnalyticsCharts();
        this.initCardEntrance();
        this.initDayCardAnimation();
        this.initCopy();
        this.initMenus();
        this.initConfirm();
        this.initJumpBar();
        this.initAudioPlayers();
        this.initAutoGrow();
        this.initPrint();
        this.initPasswordToggles();
        this.initEmojiPickers();
        this.initValueFillers();
        this.initDayTypeForms();
        this.initDayPreviews();
        this.initDayRangeCount();
        this.initJalaliDates();
    },

    /* شمارش زنده روزهای چالش از بازه تاریخ شروع و پایان:
       data-day-range-start / data-day-range-end روی دو input و
       data-day-range-output روی عنصری که تعداد روزها را نشان می‌دهد. */
    initDayRangeCount() {
        const startInput = document.querySelector('[data-day-range-start]');
        const endInput = document.querySelector('[data-day-range-end]');
        const output = document.querySelector('[data-day-range-output]');

        if (!startInput || !endInput || !output) return;
        if (startInput.dataset.bound) return;
        startInput.dataset.bound = '1';

        const dayLength = 24 * 60 * 60 * 1000;

        const update = () => {
            const start = jalaliInputToDate(startInput.value);
            const end = jalaliInputToDate(endInput.value);

            if (!start || !end) {
                output.textContent = '—';
                return;
            }

            const days = Math.round((end - start) / dayLength) + 1;

            output.textContent = days > 0
                ? `${toPersianDigits(days)} روز`
                : 'بازه اشتباه است';
        };

        [startInput, endInput].forEach((input) => {
            input.addEventListener('input', update);
            input.addEventListener('change', update);
        });

        update();
    },

    /* مدال انتخاب تاریخ شمسی روی input های دارای data-jalali-picker */
    initJalaliDates() {
        initJalaliPickers();
    },

    /* انتخاب نماد چالش: data-emoji="#input" data-emoji-value="🦋" */
    initEmojiPickers() {
        document.querySelectorAll('[data-emoji]').forEach((button) => {
            if (button.dataset.bound) return;
            button.dataset.bound = '1';

            button.addEventListener('click', () => {
                const input = document.querySelector(button.getAttribute('data-emoji'));
                if (!input) return;

                input.value = button.getAttribute('data-emoji-value') ?? button.textContent.trim();
            });
        });
    },

    /* پر کردن یک input با مقدار ثابت: data-fill-target="#input" data-fill-value="۱۴۰۵/۰۷/۲۱" */
    initValueFillers() {
        document.querySelectorAll('[data-fill-target]').forEach((button) => {
            if (button.dataset.bound) return;
            button.dataset.bound = '1';

            button.addEventListener('click', () => {
                const input = document.querySelector(button.getAttribute('data-fill-target'));
                if (!input) return;

                input.value = button.getAttribute('data-fill-value') ?? '';
                input.dispatchEvent(new Event('change', { bubbles: true }));
                input.focus();
            });
        });
    },

    /* فرم «افزودن به روز»: انتخاب نوع محتوا (کار یا پرسش) و نمایش فیلدهای مربوط به آن */
    initDayTypeForms() {
        document.querySelectorAll('form[data-day-type-form]').forEach((form) => {
            if (form.dataset.bound) return;
            form.dataset.bound = '1';

            const radios = Array.from(form.querySelectorAll('input[name="type"]'));
            const groups = Array.from(form.querySelectorAll('[data-day-group]'));
            const labels = Array.from(form.querySelectorAll('[data-day-type-label]'));

            const sync = () => {
                const value = radios.find((radio) => radio.checked)?.value ?? 'task';

                groups.forEach((group) => {
                    const active = group.getAttribute('data-day-group') === value;
                    group.classList.toggle('hidden', !active);
                    group.classList.toggle('grid', active);

                    // فیلدهای گروه پنهان ارسال نمی‌شوند تا اعتبارسنجی مرورگر گیر نکند
                    group.querySelectorAll('input, textarea, select').forEach((field) => {
                        field.disabled = !active;
                    });
                });

                labels.forEach((label) => {
                    const active = label.getAttribute('data-day-type-label') === value;
                    label.classList.toggle('bg-forest', active);
                    label.classList.toggle('text-white', active);
                    label.classList.toggle('ring-forest', active);
                });
            };

            radios.forEach((radio) => radio.addEventListener('change', sync));
            sync();
        });
    },

    /* نمایش زنده «روز چندم» زیر فیلد تاریخ: data-day-preview="#target" data-day-start="۱۴۰۵/۰۷/۱۸" */
    initDayPreviews() {
        document.querySelectorAll('input[data-day-preview]').forEach((input) => {
            if (input.dataset.bound) return;
            input.dataset.bound = '1';

            const target = document.querySelector(input.getAttribute('data-day-preview'));
            const start = jalaliInputToDate(input.getAttribute('data-day-start'));
            const startLabel = input.getAttribute('data-day-start-label') ?? '';

            if (!target || !start) return;

            const dayLength = 24 * 60 * 60 * 1000;

            const update = () => {
                const date = jalaliInputToDate(input.value);

                if (!date) {
                    target.textContent = 'تاریخ را انتخاب کن تا روزش را نشان بدهم.';
                    target.classList.remove('text-danger');
                    return;
                }

                const day = Math.round((date - start) / dayLength) + 1;

                if (day < 1) {
                    target.textContent = `این تاریخ قبل از شروع چالش است (روز ۱: ${startLabel}).`;
                    target.classList.add('text-danger');
                    return;
                }

                target.textContent = `این محتوا در روز ${toPersianDigits(day)} از چالش ثبت می‌شود.`;
                target.classList.remove('text-danger');
            };

            input.addEventListener('input', update);
            input.addEventListener('change', update);
            update();
        });
    },

    /* نمایش/پنهان کردن رمز عبور: data-password-toggle="#selector" */
    initPasswordToggles() {
        document.querySelectorAll('[data-password-toggle]').forEach((toggle) => {
            if (toggle.dataset.bound) return;
            toggle.dataset.bound = '1';

            toggle.addEventListener('click', () => {
                const input = document.querySelector(toggle.getAttribute('data-password-toggle'));
                if (!input) return;

                const isPassword = input.getAttribute('type') === 'password';
                input.setAttribute('type', isPassword ? 'text' : 'password');
                toggle.textContent = isPassword ? '🙈' : '👁';
            });
        });
    },

    /* چاپ صفحه: data-print */
    initPrint() {
        if (this._printBound) return;
        this._printBound = true;

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-print]');
            if (!trigger) return;

            event.preventDefault();
            window.print();
        });
    },

    /* کپی کردن متن در کلیپ‌بورد: data-copy="متن" یا data-copy-target="#selector" */
    initCopy() {
        if (this._copyBound) return;
        this._copyBound = true;

        document.addEventListener('click', async (event) => {
            const trigger = event.target.closest('[data-copy], [data-copy-target]');
            if (!trigger) return;

            event.preventDefault();

            const targetSelector = trigger.getAttribute('data-copy-target');
            const value = targetSelector
                ? document.querySelector(targetSelector)?.value ?? ''
                : trigger.getAttribute('data-copy') ?? '';

            if (!value) return;

            try {
                await navigator.clipboard.writeText(value);
            } catch {
                const helper = document.createElement('textarea');
                helper.value = value;
                helper.setAttribute('readonly', '');
                helper.style.position = 'fixed';
                helper.style.opacity = '0';
                document.body.appendChild(helper);
                helper.select();
                document.execCommand('copy');
                helper.remove();
            }

            const original = trigger.dataset.originalLabel ?? trigger.innerHTML;
            trigger.dataset.originalLabel = original;
            trigger.innerHTML = 'کپی شد ✓';

            window.setTimeout(() => {
                trigger.innerHTML = original;
            }, 1600);
        });
    },

    /* منوی موبایل */
    initMenus() {
        const toggles = document.querySelectorAll('[data-menu-toggle]');
        toggles.forEach((toggle) => {
            if (toggle.dataset.bound) return;
            toggle.dataset.bound = '1';

            toggle.addEventListener('click', () => {
                const target = document.querySelector(toggle.getAttribute('data-menu-toggle'));
                target?.classList.toggle('hidden');
            });
        });
    },

    /* تأیید قبل از حذف: data-confirm="متن پرسش" */
    initConfirm() {
        if (this._confirmBound) return;
        this._confirmBound = true;

        document.addEventListener('submit', (event) => {
            const form = event.target.closest('form[data-confirm]');
            if (!form) return;

            if (!window.confirm(form.getAttribute('data-confirm'))) {
                event.preventDefault();
            }
        });
    },

    /* نوار پرش سریع: باز کردن کارت روز و اسکرول نرم -------------------- */
    initJumpBar() {
        const openAndScroll = (target) => {
            if (!target) return;

            if (target.tagName === 'DETAILS') {
                target.open = true;
            }

            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        };

        // چیپ روزها
        document.querySelectorAll('[data-jump-day]').forEach((chip) => {
            if (chip.dataset.bound) return;
            chip.dataset.bound = '1';

            chip.addEventListener('click', (event) => {
                const target = document.getElementById(chip.dataset.jumpDay);
                if (!target) return;

                event.preventDefault();
                history.replaceState(null, '', `#${chip.dataset.jumpDay}`);
                openAndScroll(target);
            });
        });

        // چیپ‌هایی که دو نسخه دارند (مثل فایل‌ها: موبایل/دسکتاپ)
        document.querySelectorAll('[data-jump-any]').forEach((chip) => {
            if (chip.dataset.bound) return;
            chip.dataset.bound = '1';

            chip.addEventListener('click', (event) => {
                const candidates = Array.from(document.querySelectorAll(`[data-jump-anchor="${chip.dataset.jumpAny}"]`));
                const visible = candidates.find((el) => el.offsetParent !== null) || candidates[0];

                if (!visible) return;

                event.preventDefault();
                openAndScroll(visible);
            });
        });

        // اگر آدرس با #day-N باز شود، همان روز باز بماند (فقط یک بار)
        if (window.location.hash && !this._hashHandled) {
            this._hashHandled = true;
            const target = document.querySelector(window.location.hash);

            if (target && target.tagName === 'DETAILS') {
                target.open = true;
            }
        }
    },

    /* پخش‌کننده اختصاصی فایل‌های صوتی:
       بدون پخش‌کننده پیش‌فرض مرورگر، با نوار پیشرفت لمسی، زمان، سرعت پخش و
       قاعده «فقط یک صوت در حال پخش». */
    initAudioPlayers() {
        const toFaTime = (seconds) => {
            if (!Number.isFinite(seconds) || seconds < 0) seconds = 0;

            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            const pad = (value) => String(value).padStart(2, '0');

            return toPersianDigits(`${pad(mins)}:${pad(secs)}`);
        };

        const players = Array.from(document.querySelectorAll('[data-audio-player]'));
        if (!players.length) return;

        let current = null;

        players.forEach((player) => {
            if (player.dataset.bound) return;
            player.dataset.bound = '1';

            const audio = new Audio(player.dataset.audioSrc);
            audio.preload = 'metadata';

            // آمار دقیق پخش: فقط وقتی کاربر واقعاً دکمهٔ پخش را بزند
            // (یک‌بار در هر بار باز شدن صفحه)؛ preload مرورگر شمرده نمی‌شود.
            // در پنل (پیش‌نمایش مدیر) شمرده نمی‌شود.
            if (player.dataset.audioPlayUrl && !window.location.pathname.startsWith('/panel')) {
                audio.addEventListener('play', () => {
                    if (player.dataset.playSent) return;
                    player.dataset.playSent = '1';

                    const body = new FormData();
                    body.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

                    fetch(player.dataset.audioPlayUrl, {
                        method: 'POST',
                        body,
                        keepalive: true,
                    }).catch(() => {});
                });
            }

            const toggle = player.querySelector('[data-audio-toggle]');
            const seek = player.querySelector('[data-audio-seek]');
            const speed = player.querySelector('[data-audio-speed]');
            const currentLabel = player.querySelector('[data-audio-current]');
            const durationLabel = player.querySelector('[data-audio-duration]');
            const icons = {
                play: player.querySelector('[data-audio-icon="play"]'),
                pause: player.querySelector('[data-audio-icon="pause"]'),
            };

            const speeds = [1, 1.25, 1.5, 0.75];
            let speedIndex = 0;
            let dragging = false;

            const paint = () => {
                const duration = Number.isFinite(audio.duration) ? audio.duration : 0;
                const progress = duration > 0 ? (audio.currentTime / duration) * 100 : 0;

                seek.style.setProperty('--audio-progress', `${progress.toFixed(2)}%`);

                if (!dragging) {
                    seek.value = String(Math.round(progress * 10));
                }

                currentLabel.textContent = toFaTime(audio.currentTime);
                durationLabel.textContent = toFaTime(duration);
            };

            const setPlaying = (playing) => {
                toggle.setAttribute('aria-pressed', playing ? 'true' : 'false');
                toggle.setAttribute('aria-label', `${playing ? 'توقف' : 'پخش'} فایل صوتی ${player.dataset.audioTitle}`);
                icons.play.classList.toggle('hidden', playing);
                icons.pause.classList.toggle('hidden', !playing);
                player.classList.toggle('is-playing', playing);
            };

            toggle.addEventListener('click', () => {
                if (audio.paused) {
                    if (current && current !== audio) {
                        current.pause();
                    }

                    current = audio;
                    audio.play().catch(() => setPlaying(false));
                } else {
                    audio.pause();
                }
            });

            seek.addEventListener('pointerdown', () => { dragging = true; });
            seek.addEventListener('pointerup', () => { dragging = false; });

            seek.addEventListener('input', () => {
                const duration = Number.isFinite(audio.duration) ? audio.duration : 0;
                const ratio = Number(seek.value) / 1000;

                seek.style.setProperty('--audio-progress', `${(ratio * 100).toFixed(2)}%`);
                currentLabel.textContent = toFaTime(duration * ratio);
            });

            seek.addEventListener('change', () => {
                const duration = Number.isFinite(audio.duration) ? audio.duration : 0;

                if (duration > 0) {
                    audio.currentTime = duration * (Number(seek.value) / 1000);
                }

                dragging = false;
                paint();
            });

            speed.addEventListener('click', () => {
                speedIndex = (speedIndex + 1) % speeds.length;
                audio.playbackRate = speeds[speedIndex];
                speed.textContent = toPersianDigits(speeds[speedIndex]) + '×';
            });

            audio.addEventListener('play', () => setPlaying(true));
            audio.addEventListener('pause', () => setPlaying(false));
            audio.addEventListener('ended', () => {
                audio.currentTime = 0;
                setPlaying(false);
                paint();
            });
            audio.addEventListener('loadedmetadata', paint);
            audio.addEventListener('timeupdate', paint);
            audio.addEventListener('error', () => {
                toggle.disabled = true;
                durationLabel.textContent = 'پخش نشد';
            });

            paint();
        });
    },

    /* ذخیره بدون رفرش صفحه: فرم‌های data-ajax-save با fetch ثبت می‌شوند،
       دکمه ذخیره حالت لودینگ می‌گیرد و بعد از ذخیره، فقط بخش‌های مربوطه
       (خلاصه، باکس روزها، نکته‌ها) در همان صفحه جای‌گذاری می‌شوند. */
    initAjaxSave() {
        if (this._ajaxBound) return;
        this._ajaxBound = true;

        document.addEventListener('submit', (event) => {
            const form = event.target.closest('form[data-ajax-save]');
            if (!form || event.defaultPrevented) return;

            event.preventDefault();
            this.submitFormAjax(form);
        });
    },

    async submitFormAjax(form) {
        if (form.dataset.saving) return;
        form.dataset.saving = '1';

        const button = form.querySelector('button[type="submit"], input[type="submit"]');
        const originalLabel = button && button.tagName === 'BUTTON' ? button.innerHTML : null;

        if (button) {
            button.disabled = true;

            if (button.tagName === 'BUTTON') {
                button.classList.add('is-loading');
                button.insertAdjacentHTML('afterbegin', '<span class="btn-spinner"></span> ');
            }
        }

        form.setAttribute('aria-busy', 'true');

        const finish = () => {
            delete form.dataset.saving;
            form.removeAttribute('aria-busy');

            if (button) {
                button.disabled = false;

                if (button.tagName === 'BUTTON') {
                    button.classList.remove('is-loading');
                    button.innerHTML = originalLabel;
                }
            }
        };

        try {
            // آپلود فایل با XHR می‌رود تا درصد پیشرفت نشان داده شود
            if (this.formHasFile(form)) {
                await this.uploadFormWithProgress(form);

                return;
            }

            const response = await fetch(form.getAttribute('action') || window.location.href, {
                method: (form.getAttribute('method') || 'POST').toUpperCase(),
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                redirect: 'manual',
            });

            // پاسخ موفق = ریدایرکت (Laravel با back() برمی‌گرداند)
            if (response.type === 'opaqueredirect') {
                const stayOpen = form.hasAttribute('data-stay-open');

                if (!stayOpen) this.closeModalFor(form);

                const message = await this.refreshDynamicSections();

                if (stayOpen) {
                    this.resetModalForm(form);
                    this.showToast(`${message || 'ذخیره شد.'} — فرم خالی شد؛ مورد بعدی را اضافه کن.`, 'status');
                } else {
                    this.showToast(message || 'ذخیره شد.', 'status');
                }

                return;
            }

            if (response.status === 422) {
                const data = await response.json().catch(() => null);
                const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;

                this.showToast(firstError || data?.message || 'مقادیر فرم درست نیست.', 'error');

                return;
            }

            this.showToast('ذخیره انجام نشد؛ دوباره تلاش کن.', 'error');
        } catch {
            this.showToast('اتصال برقرار نشد؛ دوباره تلاش کن.', 'error');
        } finally {
            finish();
        }
    },

    /* بخش‌های صفحه را با نسخه تازه جای‌گذاری می‌کند (بدون رفرش کل صفحه)
       و متن پیام موفقیت را از همان نسخه برمی‌گرداند. */
    async refreshDynamicSections() {
        const openIds = new Set(
            Array.from(document.querySelectorAll('details[open][id]')).map((details) => details.id)
        );

        const response = await fetch(window.location.href, {
            headers: { 'X-Panel-Refresh': '1' },
        });

        const parsed = new DOMParser().parseFromString(await response.text(), 'text/html');

        const flash = parsed.querySelector('[data-flash]');
        const message = flash
            ? (flash.querySelector('ul li, span:last-child')?.textContent.trim() || null)
            : null;

        ['challenge-stats', 'tasks', 'tips'].forEach((id) => {
            const fresh = parsed.getElementById(id);
            const current = document.getElementById(id);

            if (fresh && current) {
                current.replaceWith(fresh);
            }
        });

        const freshSubtitle = parsed.getElementById('page-subtitle');
        const subtitle = document.getElementById('page-subtitle');

        if (freshSubtitle && subtitle) {
            subtitle.textContent = freshSubtitle.textContent;
        }

        this.initCardEntrance();
        this.initDayCardAnimation();

        // باکس‌هایی که کاربر باز کرده بود، باز بمانند
        document.querySelectorAll('details[id]').forEach((details) => {
            details.open = openIds.has(details.id);
        });

        // اسکریپت‌ها را برای محتوای تازه فعال کن (با گارد، دوباره‌بار امن است)
        this.init();

        return message;
    },

    /* انیمیشن ورود کارت‌ها: هر کارت کمی دیرتر از قبلی ظاهر می‌شود.
       بعد از رفرش جزئی بخش‌ها هم کارت‌های تازه همان‌طور می‌آیند. */
    initCardEntrance() {
        document.querySelectorAll('.card, .day-card, .day-group, .surface').forEach((el, index) => {
            if (el.dataset.entered) return;
            el.dataset.entered = '1';
            el.style.animationDelay = `${Math.min(index, 10) * 45}ms`;
        });
    },

    /* نمودارهای صفحهٔ «آمار بازدید» با chart.js — فقط همان‌جا بارگذاری می‌شود */
    initAnalyticsCharts() {
        const dataTag = document.getElementById('analytics-data');
        if (!dataTag) return;

        import('chart.js/auto').then(({ default: Chart }) => {
            const data = JSON.parse(dataTag.textContent);
            const fa = (value) => toPersianDigits(value);

            Chart.defaults.font.family = "'Vazirmatn Variable', ui-sans-serif, system-ui, sans-serif";
            Chart.defaults.color = '#566f64';
            Chart.defaults.plugins.tooltip.callbacks = {
                label: (item) => `${item.dataset.label || item.label}: ${fa(item.parsed.y ?? item.parsed)}`,
            };

            const grid = { color: 'rgba(202, 220, 193, 0.5)' };

            // خطی: بازدید روزانه ۳۰ روز اخیر با تفکیک نوع
            new Chart(document.getElementById('chart-daily'), {
                type: 'line',
                data: {
                    labels: data.daily.labels,
                    datasets: [
                        { label: 'صفحه چالش', data: data.daily.challenge, borderColor: '#659287', backgroundColor: 'rgba(101, 146, 135, 0.14)', fill: true, tension: 0.35, pointRadius: 2 },
                        { label: 'کارت روز', data: data.daily.day, borderColor: '#D9B96E', backgroundColor: 'rgba(217, 185, 110, 0.14)', fill: true, tension: 0.35, pointRadius: 2 },
                        { label: 'داشبورد شرکت‌کننده', data: data.daily.dashboard, borderColor: '#A8B89A', backgroundColor: 'rgba(168, 184, 154, 0.16)', fill: true, tension: 0.35, pointRadius: 2 },
                        { label: 'صفحه اصلی', data: data.daily.home, borderColor: '#4E7268', backgroundColor: 'rgba(78, 114, 104, 0.12)', fill: true, tension: 0.35, pointRadius: 2 },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0, callback: (v) => fa(v) }, grid },
                        x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkipPadding: 12 } },
                    },
                },
            });

            // ستونی: جمع ماهانه شمسی
            new Chart(document.getElementById('chart-monthly'), {
                type: 'bar',
                data: {
                    labels: data.monthly.labels,
                    datasets: [{ label: 'بازدید ماه', data: data.monthly.totals, backgroundColor: 'rgba(217, 185, 110, 0.75)', borderColor: '#D9B96E', borderWidth: 1, borderRadius: 8 }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0, callback: (v) => fa(v) }, grid },
                        x: { grid: { display: false } },
                    },
                },
            });

            // دونات: سهم هر نوع بازدید در ۳۰ روز اخیر
            new Chart(document.getElementById('chart-types'), {
                type: 'doughnut',
                data: {
                    labels: data.types.labels,
                    datasets: [{ data: data.types.totals, backgroundColor: ['#659287', '#D9B96E', '#A8B89A', '#4E7268'], borderColor: '#ffffff', borderWidth: 2 }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: (item) => `${item.label}: ${fa(item.parsed)}`,
                            },
                        },
                    },
                },
            });

            // ستونی افقی: چالش‌ها در ۳۰ روز اخیر (فقط وقتی بومش هست)
            const challengesEl = document.getElementById('chart-challenges');

            if (challengesEl) new Chart(challengesEl, {
                type: 'bar',
                data: {
                    labels: data.challenges.labels,
                    datasets: [{ label: 'بازدید ۳۰ روز', data: data.challenges.totals, backgroundColor: 'rgba(101, 146, 135, 0.75)', borderColor: '#659287', borderWidth: 1, borderRadius: 8 }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { beginAtZero: true, ticks: { precision: 0, callback: (v) => fa(v) }, grid },
                        y: { grid: { display: false } },
                    },
                },
            });
        }).catch(() => {});
    },

    /* آمار بازدید: وقتی کارت روز باز می‌شود، یک beacon کوچک «دیده‌شدن روز»
       می‌فرستد (یک‌بار در هر نشست مرورگر، برای هر روز و هر چالش). */
    /* باز و بسته شدن نرم کارت روزها در سایت: ارتفاع با انیمیشن جمع/باز
       می‌شود و محتوای روز کمی محو/نمایان می‌شود. */
    initDayCardAnimation() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        document.querySelectorAll('details.day-card').forEach((card) => {
            if (card.dataset.animBound) return;
            card.dataset.animBound = '1';

            const summary = card.querySelector(':scope > summary');
            const body = card.querySelector(':scope > .day-body');
            if (!summary || !body) return;

            summary.addEventListener('click', (event) => {
                event.preventDefault();
                if (card._dayAnim) card._dayAnim.cancel();

                const closedHeight = summary.offsetHeight;
                const openHeight = card.scrollHeight;
                const timing = { easing: 'cubic-bezier(0.22, 0.8, 0.36, 1)' };

                if (card.open) {
                    card._dayAnim = card.animate(
                        [{ height: `${openHeight}px` }, { height: `${closedHeight}px` }],
                        { ...timing, duration: 300 }
                    );
                    body.animate([{ opacity: 1 }, { opacity: 0 }], { duration: 180, easing: 'ease-in' });
                    card._dayAnim.onfinish = () => {
                        card.open = false;
                        card._dayAnim = null;
                    };
                } else {
                    card.open = true;
                    card._dayAnim = card.animate(
                        [{ height: `${closedHeight}px` }, { height: `${openHeight}px` }],
                        { ...timing, duration: 340 }
                    );
                    body.animate(
                        [{ opacity: 0, transform: 'translateY(-6px)' }, { opacity: 1, transform: 'none' }],
                        { duration: 300, easing: 'ease-out' }
                    );
                    card._dayAnim.onfinish = () => {
                        card._dayAnim = null;
                    };
                }
            });
        });
    },

    initDayViewTracking() {
        document.querySelectorAll('details.day-card[data-day-view-url]').forEach((card) => {
            const day = parseInt(card.getAttribute('data-day-view-number') || '0', 10);
            if (!day) return;

            const send = () => {
                const key = `dayview:${card.getAttribute('data-day-view-url')}:${day}`;

                if (sessionStorage.getItem(key)) return;
                sessionStorage.setItem(key, '1');

                const body = new FormData();
                body.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                body.append('day_number', String(day));

                fetch(card.getAttribute('data-day-view-url'), {
                    method: 'POST',
                    body,
                    keepalive: true,
                }).catch(() => {});
            };

            // کارت روز جاری از قبل باز است — همان باز شدن اول هم یک بازدید است
            if (card.open) send();

            card.addEventListener('toggle', () => {
                if (card.open) send();
            });
        });
    },

    /* مودال‌های افزودن: دکمه data-open-modal یک <dialog> را با روز از پیش
       انتخاب‌شده باز می‌کند؛ بعد از ذخیره موفق، مودال بسته می‌شود. */
    initModals() {
        if (this._modalsBound) return;
        this._modalsBound = true;

        document.addEventListener('click', (event) => {
            const opener = event.target.closest('[data-open-modal]');

            if (opener) {
                event.preventDefault();
                this.openModal(opener);

                return;
            }

            if (event.target.closest('[data-close-modal]')) {
                event.preventDefault();
                event.target.closest('dialog')?.close();
            }
        });

        // کلیک روی پس‌زمینه تیره = بستن مودال
        document.querySelectorAll('dialog.panel-modal').forEach((dialog) => {
            if (dialog.dataset.bound) return;
            dialog.dataset.bound = '1';

            dialog.addEventListener('click', (event) => {
                if (event.target === dialog) dialog.close();
            });
        });
    },

    openModal(opener) {
        const dialog = document.getElementById(opener.getAttribute('data-open-modal'));
        if (!dialog) return;

        const day = opener.getAttribute('data-modal-day') ?? '';
        const type = opener.getAttribute('data-modal-type');

        dialog.querySelectorAll('[data-modal-day-input]').forEach((input) => {
            input.value = day;
        });

        dialog.querySelectorAll('[data-modal-day-label]').forEach((label) => {
            label.textContent = day ? `— روز ${toPersianDigits(day)}` : '— بدون روز مشخص';
        });

        // عنوان مودال از دکمه می‌آید (مثلاً «افزودن پرسش» به‌جای «افزودن کار یا پرسش»)
        const title = opener.getAttribute('data-modal-title');

        dialog.querySelectorAll('[data-modal-heading]').forEach((heading) => {
            heading.dataset.defaultHeading ??= heading.textContent.trim();
            heading.textContent = title || heading.dataset.defaultHeading;
        });

        // سوییچ «کار یا پرسش» فقط وقتی دیده می‌شود که دکمه نوع را تعیین نکرده باشد
        const switcher = dialog.querySelector('[data-day-type-switch]');

        if (switcher) {
            switcher.classList.toggle('hidden', Boolean(type));
        }

        if (type) {
            const radio = dialog.querySelector(`input[name="type"][value="${type}"]`);

            if (radio) {
                radio.checked = true;
                radio.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        } else {
            dialog.setAttribute('open', '');
        }

        dialog.querySelector('input:not([type="hidden"]), textarea, select')?.focus();
    },

    closeModalFor(form) {
        const dialog = form.closest('dialog');
        if (dialog?.open) dialog.close();
    },

    /* بعد از ذخیره موفق در مودال‌های data-stay-open: فرم خالی می‌شود تا
       مورد بعدی را بشود افزود، اما روز (و نوع محتوا) همان روز می‌ماند. */
    resetModalForm(form) {
        const day = form.querySelector('[data-modal-day-input]')?.value ?? '';
        const type = form.querySelector('input[name="type"]:checked')?.value ?? null;

        form.reset();

        const dayInput = form.querySelector('[data-modal-day-input]');
        if (dayInput) dayInput.value = day;

        if (type) {
            const radio = form.querySelector(`input[name="type"][value="${type}"]`);

            if (radio) {
                radio.checked = true;
                radio.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        form.querySelector('input:not([type="hidden"]), textarea')?.focus();
    },

    /* فرم فایل انتخاب‌شده دارد؟ (برای تصمیم بین fetch و XHR) */
    formHasFile(form) {
        return Array.from(form.querySelectorAll('input[type="file"]')).some(
            (input) => input.files && input.files.length > 0
        );
    },

    /* نوار پیشرفت آپلود بالای صفحه */
    showUploadProgress(percent) {
        let bar = document.querySelector('[data-upload-progress]');

        if (!bar) {
            bar = document.createElement('div');
            bar.className = 'upload-progress';
            bar.setAttribute('data-upload-progress', '');
            bar.setAttribute('role', 'progressbar');
            bar.setAttribute('aria-valuemin', '0');
            bar.setAttribute('aria-valuemax', '100');
            bar.setAttribute('aria-label', 'پیشرفت آپلود فایل');
            bar.innerHTML = `
                <p class="upload-progress-label">⬆️ در حال آپلود فایل… <span data-upload-percent>۰٪</span></p>
                <div class="upload-progress-track"><span class="upload-progress-fill" data-upload-fill></span></div>`;

            // مثل توست، در لایهٔ بالا تا زیر مودال آپلود تار نشود
            if (typeof bar.showPopover === 'function') {
                bar.setAttribute('popover', 'manual');
            }

            document.body.appendChild(bar);

            if (typeof bar.showPopover === 'function') {
                try {
                    bar.showPopover();
                } catch {
                    // مرورگر قدیمی: رفتار قبلی
                }
            }
        }

        const fill = bar.querySelector('[data-upload-fill]');
        const label = bar.querySelector('[data-upload-percent]');

        if (fill) fill.style.width = `${percent}%`;
        if (label) label.textContent = `${toPersianDigits(String(percent))}٪`;
        bar.setAttribute('aria-valuenow', String(percent));
        bar.classList.remove('is-out');
    },

    hideUploadProgress() {
        const bar = document.querySelector('[data-upload-progress]');
        if (!bar) return;

        bar.classList.add('is-out');
        window.setTimeout(() => bar.remove(), 500);
    },

    /* ارسال فرم با XHR تا رویداد پیشرفت آپلود در دسترس باشد */
    xhrUpload(form) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();

            xhr.open('POST', form.getAttribute('action') || window.location.href, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.upload.addEventListener('progress', (event) => {
                if (event.lengthComputable) {
                    this.showUploadProgress(Math.min(99, Math.round((event.loaded / event.total) * 100)));
                }
            });

            xhr.addEventListener('load', () => {
                this.showUploadProgress(100);
                window.setTimeout(() => this.hideUploadProgress(), 400);
                resolve(xhr);
            });

            xhr.addEventListener('error', () => {
                this.hideUploadProgress();
                reject(new Error('network'));
            });

            xhr.addEventListener('abort', () => {
                this.hideUploadProgress();
                reject(new Error('abort'));
            });

            xhr.send(new FormData(form));
        });
    },

    /* فرم‌های data-ajax-save که فایل دارند: آپلود با نوار پیشرفت،
       بعد از پایان همان جریان جای‌گذاری بخش‌ها ادامه پیدا می‌کند. */
    async uploadFormWithProgress(form) {
        try {
            const xhr = await this.xhrUpload(form);

            if (xhr.status >= 200 && xhr.status < 300) {
                const stayOpen = form.hasAttribute('data-stay-open');

                if (!stayOpen) this.closeModalFor(form);

                const message = await this.refreshDynamicSections();

                if (stayOpen) {
                    this.resetModalForm(form);
                    this.showToast(`${message || 'ذخیره شد.'} — فرم خالی شد؛ مورد بعدی را اضافه کن.`, 'status');
                } else {
                    this.showToast(message || 'ذخیره شد.', 'status');
                }

                return;
            }

            if (xhr.status === 422) {
                let data = null;

                try {
                    data = JSON.parse(xhr.responseText);
                } catch {
                    data = null;
                }

                const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
                this.showToast(firstError || data?.message || 'مقادیر فرم درست نیست.', 'error');

                return;
            }

            this.showToast('ذخیره انجام نشد؛ دوباره تلاش کن.', 'error');
        } catch {
            this.showToast('آپلود انجام نشد؛ دوباره تلاش کن.', 'error');
        }
    },

    /* فرم‌های آپلودی که data-ajax-save ندارند (مثل ضمائم چالش):
       ارسال با XHR + نوار پیشرفت و بعد هدایت به صفحه نتیجه. */
    initUploadProgress() {
        if (this._uploadBound) return;
        this._uploadBound = true;

        document.addEventListener('submit', (event) => {
            const form = event.target.closest('form');
            if (!form || event.defaultPrevented) return;
            if (form.hasAttribute('data-ajax-save')) return; // مسیر ajax-save خودش نوار دارد
            if (!this.formHasFile(form)) return;

            event.preventDefault();
            this.submitPlainFormWithProgress(form);
        });
    },

    async submitPlainFormWithProgress(form) {
        if (form.dataset.saving) return;
        form.dataset.saving = '1';

        const button = form.querySelector('button[type="submit"], input[type="submit"]');
        const originalLabel = button && button.tagName === 'BUTTON' ? button.innerHTML : null;

        if (button) {
            button.disabled = true;

            if (button.tagName === 'BUTTON') {
                button.classList.add('is-loading');
                button.insertAdjacentHTML('afterbegin', '<span class="btn-spinner"></span> ');
            }
        }

        const finish = () => {
            delete form.dataset.saving;

            if (button) {
                button.disabled = false;

                if (button.tagName === 'BUTTON') {
                    button.classList.remove('is-loading');
                    button.innerHTML = originalLabel;
                }
            }
        };

        try {
            const xhr = await this.xhrUpload(form);

            if (xhr.status >= 200 && xhr.status < 300) {
                // XHR ریدایرکت را خودش دنبال می‌کند؛ به همان صفحه می‌رویم
                window.location.assign(xhr.responseURL || window.location.href);

                return;
            }

            if (xhr.status === 422) {
                let data = null;

                try {
                    data = JSON.parse(xhr.responseText);
                } catch {
                    data = null;
                }

                const firstError = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
                this.showToast(firstError || data?.message || 'مقادیر فرم درست نیست.', 'error');

                return;
            }

            this.showToast('آپلود انجام نشد؛ دوباره تلاش کن.', 'error');
        } catch {
            this.showToast('آپلود انجام نشد؛ دوباره تلاش کن.', 'error');
        } finally {
            finish();
        }
    },

    /* پیام کوتاه پایین صفحه (موفقیت/خطا) */
    showToast(message, type = 'status') {
        document.querySelectorAll('.toast').forEach((toast) => toast.remove());

        const toast = document.createElement('div');
        toast.className = `toast ${type === 'error' ? 'toast-error' : 'toast-success'}`;
        toast.setAttribute('role', 'status');
        toast.textContent = message;

        // مودال با showModal در «لایهٔ بالا» (top layer) مرورگر است و هر
        // z-index معمولی زیرش تار می‌شود؛ popover هم در همان لایهٔ بالا
        // می‌نشیند و چون بعد از مودال باز می‌شود، رویش دیده می‌شود.
        if (typeof toast.showPopover === 'function') {
            toast.setAttribute('popover', 'manual');
        }

        document.body.appendChild(toast);

        if (typeof toast.showPopover === 'function') {
            try {
                toast.showPopover();
            } catch {
                // مرورگر قدیمی: همان رفتار قبلی با z-index
            }
        }

        window.setTimeout(() => toast.classList.add('is-out'), 3600);
        window.setTimeout(() => toast.remove(), 4100);
    },

    /* بزرگ‌شدن خودکار textarea های فهرست‌ها */
    initAutoGrow() {
        document.querySelectorAll('textarea[data-autogrow]').forEach((area) => {
            if (area.dataset.bound) return;
            area.dataset.bound = '1';

            const resize = () => {
                area.style.height = 'auto';
                area.style.height = `${Math.min(area.scrollHeight, 480)}px`;
            };
            area.addEventListener('input', resize);
            resize();
        });
    },
};

document.addEventListener('DOMContentLoaded', () => app.init());
