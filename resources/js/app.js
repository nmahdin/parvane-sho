import { initJalaliPickers, jalaliInputToDate, toPersianDigits } from './jalali-datepicker';

/**
 * اسکریپت‌های سبک سایت: انتخابگر تاریخ شمسی، کپی لینک، منوی موبایل و تأیید حذف.
 */
const app = {
    init() {
        this.initAjaxSave();
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
            const response = await fetch(form.getAttribute('action') || window.location.href, {
                method: (form.getAttribute('method') || 'POST').toUpperCase(),
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                redirect: 'manual',
            });

            // پاسخ موفق = ریدایرکت (Laravel با back() برمی‌گرداند)
            if (response.type === 'opaqueredirect') {
                const message = await this.refreshDynamicSections();
                this.showToast(message || 'ذخیره شد.', 'status');

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

        // باکس‌هایی که کاربر باز کرده بود، باز بمانند
        document.querySelectorAll('details[id]').forEach((details) => {
            details.open = openIds.has(details.id);
        });

        // اسکریپت‌ها را برای محتوای تازه فعال کن (با گارد، دوباره‌بار امن است)
        this.init();

        return message;
    },

    /* پیام کوتاه پایین صفحه (موفقیت/خطا) */
    showToast(message, type = 'status') {
        document.querySelectorAll('.toast').forEach((toast) => toast.remove());

        const toast = document.createElement('div');
        toast.className = `toast ${type === 'error' ? 'toast-error' : 'toast-success'}`;
        toast.setAttribute('role', 'status');
        toast.textContent = message;
        document.body.appendChild(toast);

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
