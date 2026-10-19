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
        this.initCustomCharts();
        this.initStickyOffsets();
        this.initCardEntrance();
        this.initDayCardAnimation();
        this.initPageLoader();
        this.initPageReadyChip();
        this.initActionMenus();
        this.initCustomSelects();
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

            const dayNumber = this.dayNumberOf(form);

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

                const message = await this.refreshDynamicSections(dayNumber);

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

    /* شمارهٔ روزی که فرم به آن مربوط است (ورودی day_number یا کارت روزِ دربردارنده) */
    dayNumberOf(form) {
        const input = form.querySelector('input[name="day_number"]');
        if (input?.value) return input.value;

        let element = form.closest('details');

        while (element) {
            const match = element.id?.match(/^day-(\d+)$/);
            if (match) return match[1];
            element = element.parentElement?.closest('details') || null;
        }

        return null;
    },

    /* بخش‌های صفحه را با نسخه تازه جای‌گذاری می‌کند (بدون رفرش کل صفحه)
       و متن پیام موفقیت را از همان نسخه برمی‌گرداند.
       اگر dayNumber داده شود، فقط کارت همان روز جای‌گذاری می‌شود — نه همهٔ روزها. */
    async refreshDynamicSections(dayNumber = null) {
        const dayId = dayNumber ? `day-${dayNumber}` : null;
        const openIds = new Set(
            Array.from(document.querySelectorAll('details[open][id]')).map((details) => details.id)
        );

        const targeted = Boolean(dayId && document.getElementById(dayId));
        const overlayHosts = targeted
            ? [document.getElementById(dayId)]
            : ['challenge-stats', 'tasks', 'tips'].map((id) => document.getElementById(id)).filter(Boolean);

        const overlays = [];

        overlayHosts.forEach((host) => {
            host.style.position = 'relative';
            const overlay = document.createElement('div');
            overlay.className = 'section-loading';
            overlay.textContent = 'در حال بارگذاری…';
            host.appendChild(overlay);
            overlays.push(overlay);
        });

        let parsed;

        try {
            const response = await fetch(window.location.href, {
                headers: { 'X-Panel-Refresh': '1' },
            });

            parsed = new DOMParser().parseFromString(await response.text(), 'text/html');
        } finally {
            overlays.forEach((overlay) => overlay.remove());
        }

        const flash = parsed.querySelector('[data-flash]');
        const message = flash
            ? (flash.querySelector('ul li, span:last-child')?.textContent.trim() || null)
            : null;

        const freshSubtitle = parsed.getElementById('page-subtitle');
        const subtitle = document.getElementById('page-subtitle');

        if (freshSubtitle && subtitle) {
            subtitle.textContent = freshSubtitle.textContent;
        }

        const freshDay = dayId ? parsed.getElementById(dayId) : null;
        const currentDay = dayId ? document.getElementById(dayId) : null;

        if (targeted && freshDay && currentDay) {
            // فقط همان روز: وضعیت باز/بستهٔ خود روز و گروه‌های داخلش حفظ می‌شود
            const wasOpen = currentDay.open;
            const dayOpenStates = new Map(
                Array.from(currentDay.querySelectorAll('details[id]')).map((details) => [details.id, details.open])
            );

            currentDay.replaceWith(freshDay);

            const replaced = document.getElementById(dayId);

            Array.from(replaced.querySelectorAll('details[id]')).forEach((details) => {
                if (dayOpenStates.has(details.id)) details.open = dayOpenStates.get(details.id);
            });

            replaced.open = wasOpen || openIds.has(dayId);

            // آمار بالای صفحه هم تازه شود (شمارنده‌ها)
            const freshStats = parsed.getElementById('challenge-stats');
            const currentStats = document.getElementById('challenge-stats');

            if (freshStats && currentStats) {
                currentStats.replaceWith(freshStats);
            }
        } else {
            ['challenge-stats', 'tasks', 'tips'].forEach((id) => {
                const fresh = parsed.getElementById(id);
                const current = document.getElementById(id);

                if (fresh && current) {
                    current.replaceWith(fresh);
                }
            });

            // باکس‌هایی که کاربر باز کرده بود، باز بمانند
            document.querySelectorAll('details[id]').forEach((details) => {
                details.open = openIds.has(details.id);
            });
        }

        this.initCardEntrance();
        this.initDayCardAnimation();

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
            // انیمیشن از ابتدا «مکث» بود؛ حالا با تأخیر درست پخش می‌شود
            el.style.animationPlayState = 'running';
        });
    },

    /* هدر چسبان کمی از بالا فاصله دارد؛ نوار ناوبری چسبان باید دقیقاً
       زیر هدر بنشیند — ارتفاع واقعی هدر اندازه‌گیری و در متغیر CSS گذاشته می‌شود. */
    initStickyOffsets() {
        const header = document.getElementById('panel-header');
        const nav = document.querySelector('.sticky-nav');
        if (!header || !nav) return;

        const update = () => {
            const gap = 12; // همان top-3 هدر
            document.documentElement.style.setProperty(
                '--sticky-nav-top',
                `${header.offsetHeight + gap * 2}px`
            );
        };

        update();

        if (!this._stickyOffsetsBound) {
            this._stickyOffsetsBound = true;

            let timer;
            window.addEventListener('resize', () => {
                window.clearTimeout(timer);
                timer = window.setTimeout(update, 120);
            });

            if (document.fonts?.ready) {
                document.fonts.ready.then(update);
            }
        }
    },

    /* نمودارهای اختصاصی صفحهٔ «آمار بازدید» — SVG/CSS ساخت خودمان،
       بدون کتابخانهٔ خارجی؛ هاور روی هر نقطه پنل اطلاعاتی نشان می‌دهد. */
    initCustomCharts() {
        const dataTag = document.getElementById('analytics-data');
        if (!dataTag) return;

        let data;
        try {
            data = JSON.parse(dataTag.textContent);
        } catch {
            return;
        }

        const fa = (value) => toPersianDigits(value);
        const COLORS = ['#659287', '#D9B96E', '#A8B89A', '#4E7268'];

        const ensureTip = (wrap) => {
            let tip = wrap.querySelector('.xchart-tip');
            if (!tip) {
                tip = document.createElement('div');
                tip.className = 'xchart-tip';
                tip.hidden = true;
                wrap.appendChild(tip);
            }
            return tip;
        };

        const showTip = (tip, wrap, clientX, html) => {
            tip.innerHTML = html;
            tip.hidden = false;
            const rect = wrap.getBoundingClientRect();
            const x = clientX - rect.left;
            const max = rect.width - tip.offsetWidth - 8;
            tip.style.left = `${Math.max(8, Math.min(x - tip.offsetWidth / 2, max))}px`;
        };

        /* --- ۱) نمودار سطحی/خطی روزانه --- */
        const areaEl = document.getElementById('xchart-daily');
        if (areaEl && data.daily?.labels?.length) {
            const labels = data.daily.labels;
            const series = [
                ['challenge', 'صفحه چالش'],
                ['day', 'کارت روز'],
                ['dashboard', 'داشبورد'],
                ['home', 'صفحه اصلی'],
            ]
                .filter(([key]) => Array.isArray(data.daily[key]))
                .map(([key, name], i) => ({ name, color: COLORS[i], values: data.daily[key] }));

            // هم‌اندازهٔ واقعی ظرف (پیکسل) تا عرض نمودار دقیق پر شود
            const W = Math.max(320, Math.round(areaEl.clientWidth || 720));
            const H = Math.max(160, Math.round(areaEl.clientHeight || 260));
            const padL = 40;
            const padR = 10;
            const padT = 12;
            const padB = 26;
            const max = Math.max(1, ...series.flatMap((serie) => serie.values));
            const xAt = (i) => padL + (i * (W - padL - padR)) / Math.max(1, labels.length - 1);
            const yAt = (v) => padT + (1 - v / max) * (H - padT - padB);

            let svg = `<svg viewBox="0 0 ${W} ${H}" class="xchart-svg" role="img" aria-label="نمودار بازدید روزانه">`;

            for (let g = 0; g <= 4; g += 1) {
                const gy = padT + (g * (H - padT - padB)) / 4;
                svg += `<line x1="${padL}" y1="${gy.toFixed(1)}" x2="${W - padR}" y2="${gy.toFixed(1)}" stroke="#D6DAC8" stroke-opacity="0.55" stroke-dasharray="3 4"/>`;
                svg += `<text x="${padL - 6}" y="${(gy + 3).toFixed(1)}" text-anchor="end" font-size="9" fill="#71817A">${fa(Math.round(max * (1 - g / 4)))}</text>`;
            }

            const step = Math.max(1, Math.ceil(labels.length / 6));
            for (let i = 0; i < labels.length; i += step) {
                svg += `<text x="${xAt(i).toFixed(1)}" y="${H - 8}" text-anchor="middle" font-size="9" fill="#71817A">${labels[i]}</text>`;
            }

            series.forEach((serie) => {
                const points = serie.values.map((v, i) => `${xAt(i).toFixed(1)},${yAt(v).toFixed(1)}`).join(' ');
                svg += `<polygon points="${xAt(0).toFixed(1)},${yAt(0).toFixed(1)} ${points} ${xAt(serie.values.length - 1).toFixed(1)},${yAt(0).toFixed(1)}" fill="${serie.color}" fill-opacity="0.10"/>`;
                svg += `<polyline points="${points}" fill="none" stroke="${serie.color}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>`;
            });

            svg += `<line class="xchart-cursor" x1="0" y1="${padT}" x2="0" y2="${H - padB}" stroke="#4E7268" stroke-opacity="0" stroke-width="1.5"/>`;
            svg += '</svg>';
            areaEl.innerHTML = svg;
            areaEl.classList.add('is-ready');

            const tip = ensureTip(areaEl);
            const cursor = areaEl.querySelector('.xchart-cursor');

            areaEl.addEventListener('pointermove', (event) => {
                const rect = areaEl.getBoundingClientRect();
                const px = ((event.clientX - rect.left) / rect.width) * W;
                let i = Math.round(((px - padL) / (W - padL - padR)) * (labels.length - 1));
                i = Math.max(0, Math.min(labels.length - 1, i));

                cursor.setAttribute('x1', xAt(i).toFixed(1));
                cursor.setAttribute('x2', xAt(i).toFixed(1));
                cursor.setAttribute('stroke-opacity', '0.4');

                const rows = series
                    .map((serie) => `<span><i style="background:${serie.color}"></i>${serie.name}: ${fa(serie.values[i] || 0)}</span>`)
                    .join('');
                const total = series.reduce((sum, serie) => sum + (serie.values[i] || 0), 0);
                showTip(tip, areaEl, event.clientX, `<b>${labels[i]}</b>${rows}<u>مجموع روز: ${fa(total)}</u>`);
            });

            areaEl.addEventListener('pointerleave', () => {
                tip.hidden = true;
                cursor.setAttribute('stroke-opacity', '0');
            });

            // با تغییر اندازهٔ پنجره، نمودار هم‌اندازهٔ ظرف از نو ساخته می‌شود
            if (!this._chartsResizeBound) {
                this._chartsResizeBound = true;

                let chartTimer;
                window.addEventListener('resize', () => {
                    window.clearTimeout(chartTimer);
                    chartTimer = window.setTimeout(() => this.initCustomCharts(), 200);
                });
            }
        }

        /* --- ۲) ستون‌های ماهانه --- */
        const barsEl = document.getElementById('xchart-monthly');
        if (barsEl && data.monthly?.totals?.length) {
            const max = Math.max(1, ...data.monthly.totals);
            barsEl.innerHTML = `<div class="xbars">${data.monthly.totals
                .map(
                    (value, i) => `
                    <div class="xbar-col">
                        <div class="xbar" style="height:${Math.max(2, (value / max) * 100)}%;background:linear-gradient(180deg,#D9B96E,#F2DDA8)"></div>
                        <span class="xbar-label">${data.monthly.labels[i]}</span>
                    </div>`
                )
                .join('')}</div>`;
            barsEl.classList.add('is-ready');

            const tip = ensureTip(barsEl);
            barsEl.querySelectorAll('.xbar-col').forEach((col, i) => {
                col.addEventListener('pointerenter', (event) => {
                    showTip(tip, barsEl, event.clientX, `<b>${data.monthly.labels[i]}</b><span>بازدید ماه: ${fa(data.monthly.totals[i])}</span>`);
                });
                col.addEventListener('pointerleave', () => {
                    tip.hidden = true;
                });
            });
        }

        /* --- ۳) دونات سهم انواع بازدید --- */
        const donutEl = document.getElementById('xchart-types');
        if (donutEl && data.types?.totals?.length) {
            const totals = data.types.totals;
            const total = totals.reduce((sum, v) => sum + v, 0);
            const R = 54;
            const C = 2 * Math.PI * R;
            let offset = 0;
            let circles = '';

            totals.forEach((value, i) => {
                const fraction = total ? value / total : 0;
                circles += `<circle class="xdonut-seg" data-i="${i}" cx="80" cy="80" r="${R}" fill="none" stroke="${COLORS[i]}" stroke-width="22" stroke-dasharray="${(fraction * C).toFixed(2)} ${C.toFixed(2)}" stroke-dashoffset="${(-offset * C).toFixed(2)}" transform="rotate(-90 80 80)"/>`;
                offset += fraction;
            });

            const legendItems = data.types.labels.map((label, i) => {
                const percent = total ? Math.round((totals[i] / total) * 100) : 0;
                return `<li data-i="${i}"><i style="background:${COLORS[i]}"></i><span>${label}</span><b>${fa(totals[i])} (${fa(percent)}٪)</b></li>`;
            }).join('');

            donutEl.innerHTML = `<div class="xdonut"><svg viewBox="0 0 160 160" class="xdonut-svg" role="img" aria-label="سهم انواع بازدید">${circles}<text x="80" y="78" text-anchor="middle" font-size="15" font-weight="700" fill="#2F3E38">${fa(total)}</text><text x="80" y="96" text-anchor="middle" font-size="9" fill="#71817A">کل بازدید</text></svg><ul class="xdonut-legend">${legendItems}</ul></div>`;
            donutEl.classList.add('is-ready');

            const tip = ensureTip(donutEl);
            donutEl.querySelectorAll('[data-i]').forEach((el) => {
                const i = Number(el.dataset.i);

                el.addEventListener('pointerenter', () => {
                    donutEl.querySelectorAll('.xdonut-seg').forEach((seg) => {
                        seg.style.opacity = seg.dataset.i === String(i) ? '1' : '0.3';
                    });
                });

                el.addEventListener('pointermove', (event) => {
                    const percent = total ? Math.round((totals[i] / total) * 100) : 0;
                    showTip(tip, donutEl, event.clientX, `<b>${data.types.labels[i]}</b><span>${fa(totals[i])} بازدید (${fa(percent)}٪)</span>`);
                });

                el.addEventListener('pointerleave', () => {
                    donutEl.querySelectorAll('.xdonut-seg').forEach((seg) => {
                        seg.style.opacity = '1';
                    });
                    tip.hidden = true;
                });
            });
        }

        /* --- ۴) میله‌های افقی چالش‌ها (بدون innerHTML برای عنوان‌ها) --- */
        const hEl = document.getElementById('xchart-challenges');
        if (hEl && data.challenges?.totals?.length) {
            const max = Math.max(1, ...data.challenges.totals);
            const tip = ensureTip(hEl);
            const wrap = document.createElement('div');
            wrap.className = 'xhbars';

            data.challenges.labels.forEach((label, i) => {
                const row = document.createElement('div');
                row.className = 'xhbar-row';

                const title = document.createElement('span');
                title.className = 'xhbar-title';
                title.textContent = label;

                const track = document.createElement('span');
                track.className = 'xhbar-track';
                const fill = document.createElement('span');
                fill.className = 'xhbar-fill';
                fill.style.width = `${Math.max(2, (data.challenges.totals[i] / max) * 100)}%`;
                track.appendChild(fill);

                const value = document.createElement('b');
                value.className = 'xhbar-value';
                value.textContent = fa(data.challenges.totals[i]);

                row.append(title, track, value);

                row.addEventListener('pointerenter', (event) => {
                    showTip(tip, hEl, event.clientX, `<b>${title.textContent}</b><span>بازدید ماه اخیر: ${fa(data.challenges.totals[i])}</span>`);
                });
                row.addEventListener('pointerleave', () => {
                    tip.hidden = true;
                });

                wrap.appendChild(row);
            });

            hEl.innerHTML = '';
            hEl.appendChild(wrap);
            hEl.classList.add('is-ready');
        }
    },

    /* انتخابگر اختصاصی: منوی پیش‌فرض مرورگر با فهرست سبک سایت جایگزین
       می‌شود؛ خود <select> پنهان می‌ماند تا فرم‌ها مثل قبل کار کنند. */
    initCustomSelects() {
        const chevron = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-4"><path d="M6 9l6 6 6-6"/></svg>';
        const check = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="csel-check size-3.5"><path d="M5 12l5 5 9-11"/></svg>';

        document.querySelectorAll('select.input').forEach((select) => {
            if (select.dataset.cselBound) return;
            select.dataset.cselBound = '1';

            const wrap = document.createElement('div');
            wrap.className = 'csel';
            select.after(wrap);
            wrap.appendChild(select);
            select.classList.add('csel-native');

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'csel-btn';
            button.setAttribute('aria-haspopup', 'listbox');
            button.setAttribute('aria-expanded', 'false');
            button.innerHTML = '<span class="csel-label"></span>' + chevron;

            const label = button.querySelector('.csel-label');
            const syncLabel = () => {
                label.textContent = select.options[select.selectedIndex]?.textContent.trim() || '';
            };
            syncLabel();

            const list = document.createElement('div');
            list.className = 'csel-list';
            list.setAttribute('role', 'listbox');

            wrap.append(button, list);

            const close = () => {
                wrap.classList.remove('is-open');
                button.setAttribute('aria-expanded', 'false');
            };

            const open = () => {
                document.querySelectorAll('.csel.is-open').forEach((other) => {
                    if (other !== wrap) other.classList.remove('is-open');
                });

                list.innerHTML = '';

                Array.from(select.options).forEach((option) => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'csel-option' + (option.value === select.value ? ' is-selected' : '');
                    item.setAttribute('role', 'option');
                    item.innerHTML = '<span></span>' + (option.value === select.value ? check : '');
                    item.querySelector('span').textContent = option.textContent.trim();

                    item.addEventListener('click', () => {
                        select.value = option.value;
                        syncLabel();
                        close();
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                    });

                    item.addEventListener('keydown', (event) => {
                        if (event.key === 'ArrowDown') {
                            event.preventDefault();
                            item.nextElementSibling?.focus();
                        } else if (event.key === 'ArrowUp') {
                            event.preventDefault();
                            (item.previousElementSibling || button).focus();
                        }
                    });

                    list.appendChild(item);
                });

                wrap.classList.add('is-open');
                button.setAttribute('aria-expanded', 'true');
                list.querySelector('.csel-option')?.focus();
            };

            button.addEventListener('click', (event) => {
                event.stopPropagation();

                if (wrap.classList.contains('is-open')) {
                    close();
                } else {
                    open();
                }
            });

            button.addEventListener('keydown', (event) => {
                if (['Enter', ' ', 'ArrowDown'].includes(event.key)) {
                    event.preventDefault();
                    open();
                }
            });

            document.addEventListener('click', (event) => {
                if (!wrap.contains(event.target)) close();
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') close();
            });
        });
    },

    /* چیپ «در حال بارگذاری…» در صفحه نمایش اطلاعات (صفحه عمومی چالش) */
    initPageReadyChip() {
        const chip = document.getElementById('page-loading-chip');
        if (!chip) return;

        const hide = () => {
            chip.classList.add('is-out');
            setTimeout(() => chip.remove(), 320);
        };

        if (document.readyState === 'complete') {
            hide();
            return;
        }

        chip.hidden = false;
        window.addEventListener('load', hide, { once: true });
        // اطمینان: حتی اگر منبعی گیر کرد، چیپ برای همیشه نماند
        setTimeout(hide, 8000);
    },

    /* منوی عملیات: پنل با position:fixed روی همه‌چیز (از جمله جدول با
       scroll) شناور می‌ماند؛ با کلیک بیرون، Escape یا اسکرول بسته می‌شود. */
    initActionMenus() {
        const closeAll = (except = null) => {
            document.querySelectorAll('details.action-menu[open]').forEach((menu) => {
                if (menu !== except) menu.open = false;
            });
        };

        const place = (menu) => {
            const panel = menu.querySelector('.action-menu-panel');
            const summary = menu.querySelector('summary');
            if (!panel || !summary) return;

            const rect = summary.getBoundingClientRect();
            panel.style.position = 'fixed';
            panel.style.insetInlineEnd = 'auto';
            panel.style.bottom = 'auto';
            panel.style.left = 'auto';
            panel.style.right = `${Math.max(8, window.innerWidth - rect.right)}px`;

            const height = panel.offsetHeight || 220;
            let top = rect.bottom + 6;

            if (top + height > window.innerHeight - 8) {
                top = Math.max(8, rect.top - height - 6);
            }

            panel.style.top = `${top}px`;
        };

        document.querySelectorAll('details.action-menu').forEach((menu) => {
            if (menu.dataset.menuBound) return;
            menu.dataset.menuBound = '1';
            menu.addEventListener('toggle', () => {
                if (menu.open) place(menu);
            });
        });

        if (this._menusDocumentBound) return;
        this._menusDocumentBound = true;

        document.addEventListener('click', (event) => {
            const menu = event.target.closest('details.action-menu');
            closeAll(menu);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeAll();
        });

        // با اسکرول/رزایز منو دوباره روی دکمه‌اش می‌نشیند (بسته نمی‌شود)
        const reposition = () => {
            const open = document.querySelector('details.action-menu[open]');
            if (open) place(open);
        };

        window.addEventListener('scroll', reposition, { capture: true, passive: true });
        window.addEventListener('resize', reposition);
    },

    /* نوار بارگذاری هنگام جابه‌جایی بین صفحات (لینک‌ها و فرم‌های غیر AJAX) */
    initPageLoader() {
        let showTimer = null;

        const show = () => {
            if (document.getElementById('page-loader')) return;
            const bar = document.createElement('div');
            bar.id = 'page-loader';
            bar.setAttribute('aria-hidden', 'true');
            document.body.appendChild(bar);
        };

        const hide = () => {
            clearTimeout(showTimer);
            document.getElementById('page-loader')?.remove();
        };

        document.addEventListener('click', (event) => {
            if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            const link = event.target.closest('a[href]');
            if (!link || link.target === '_blank' || link.hasAttribute('download')) return;

            const href = link.getAttribute('href') || '';
            if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:')) return;

            const url = new URL(link.href, window.location.href);
            if (url.origin !== window.location.origin) return;

            clearTimeout(showTimer);
            showTimer = setTimeout(show, 180);
        });

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (form.hasAttribute('data-ajax-save') || form.hasAttribute('data-stay-open')) return;
            clearTimeout(showTimer);
            showTimer = setTimeout(show, 180);
        });

        // بازگشت از bfcache یا لغو ناوبری
        window.addEventListener('pageshow', hide);
        window.addEventListener('pagehide', hide);
    },

    /* باز و بسته شدن نرم کارت روزها در سایت: ارتفاع کارت بین «فقط سرِ ردیف»
       و «ارتفاع کامل» انیمیشن می‌شود و محتوای روز محو/نمایان می‌شود. */
    initDayCardAnimation() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        document.querySelectorAll('details.day-card').forEach((card) => {
            if (card.dataset.animBound) return;
            card.dataset.animBound = '1';

            const summary = card.querySelector(':scope > summary');
            const body = card.querySelector(':scope > .day-body');
            if (!summary || !body || typeof card.animate !== 'function') return;

            const easing = 'cubic-bezier(0.22, 0.8, 0.36, 1)';
            let running = null;

            const stop = () => {
                running = null;
                card.style.height = '';
            };

            summary.addEventListener('click', (event) => {
                event.preventDefault();

                const currentHeight = card.getBoundingClientRect().height;
                const closedHeight = summary.getBoundingClientRect().height;
                if (running) {
                    running.cancel();
                    running = null;
                }

                if (card.open) {
                    running = card.animate(
                        [{ height: `${currentHeight}px` }, { height: `${closedHeight}px` }],
                        { duration: 300, easing }
                    );
                    body.animate([{ opacity: 1 }, { opacity: 0 }], { duration: 160, easing: 'ease-in' });
                    running.onfinish = () => {
                        card.open = false;
                        stop();
                    };
                } else {
                    card.open = true;
                    const openHeight = Math.max(card.scrollHeight, closedHeight + 1);
                    running = card.animate(
                        [{ height: `${closedHeight}px` }, { height: `${openHeight}px` }],
                        { duration: 340, easing }
                    );
                    body.animate(
                        [{ opacity: 0, transform: 'translateY(-6px)' }, { opacity: 1, transform: 'none' }],
                        { duration: 300, easing: 'ease-out' }
                    );
                    running.onfinish = stop;
                }

                running.oncancel = stop;
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
                this.closeModalAnimated(event.target.closest('dialog'));
            }
        });

        // کلیک روی پس‌زمینه تیره = بستن مودال
        document.querySelectorAll('dialog.panel-modal').forEach((dialog) => {
            if (dialog.dataset.bound) return;
            dialog.dataset.bound = '1';

            dialog.addEventListener('click', (event) => {
                if (event.target === dialog) this.closeModalAnimated(dialog);
            });

            // Escape هم با همان انیمیشن ببندد
            dialog.addEventListener('cancel', (event) => {
                event.preventDefault();
                this.closeModalAnimated(dialog);
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
            // انیمیشن بسته‌شدن قبلی (fill:forwards) دیالوگ را شفاف نگه می‌دارد؛
            // پیش از باز شدن دوباره همهٔ انیمیشن‌ها لغو شوند
            dialog.getAnimations().forEach((animation) => animation.cancel());
            dialog.firstElementChild?.getAnimations().forEach((animation) => animation.cancel());

            dialog.showModal();
        } else {
            dialog.setAttribute('open', '');
        }

        dialog.querySelector('input:not([type="hidden"]), textarea, select')?.focus();
    },

    /* بستن مودال با انیمیشن محو و جمع‌شدن (کلید Esc و پس‌زمینه هم همین‌طور) */
    closeModalAnimated(dialog) {
        if (!dialog || !dialog.open) return;

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || typeof dialog.animate !== 'function') {
            dialog.close();

            return;
        }

        const card = dialog.firstElementChild;
        const animations = [
            dialog.animate([{ opacity: 1 }, { opacity: 0 }], { duration: 170, easing: 'ease-in', fill: 'forwards' }),
        ];

        if (card) {
            animations.push(
                card.animate(
                    [{ transform: 'none' }, { transform: 'translateY(10px) scale(0.97)' }],
                    { duration: 170, easing: 'ease-in', fill: 'forwards' }
                )
            );
        }

        const finish = () => {
            dialog.close();

            // fill:forwards دیالوگ/کارت را شفاف نگه می‌دارد — با لغو انیمیشن‌ها
            // دفعهٔ بعد که مودال باز می‌شود کاملاً دیده شود
            dialog.getAnimations().forEach((animation) => animation.cancel());

            if (card) {
                card.getAnimations().forEach((animation) => animation.cancel());
            }
        };

        Promise.all(animations.map((animation) => animation.finished))
            .then(finish)
            .catch(finish);
    },

    closeModalFor(form) {
        const dialog = form.closest('dialog');
        if (dialog?.open) this.closeModalAnimated(dialog);
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
        const dayNumber = this.dayNumberOf(form);

        try {
            const xhr = await this.xhrUpload(form);

            if (xhr.status >= 200 && xhr.status < 300) {
                const stayOpen = form.hasAttribute('data-stay-open');

                if (!stayOpen) this.closeModalFor(form);

                const message = await this.refreshDynamicSections(dayNumber);

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

        window.setTimeout(() => toast.classList.add('is-out'), 5000);
        window.setTimeout(() => toast.remove(), 5500);
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
