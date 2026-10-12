import { initJalaliPickers, jalaliInputToDate, toPersianDigits } from './jalali-datepicker';

/**
 * اسکریپت‌های سبک سایت: انتخابگر تاریخ شمسی، کپی لینک، منوی موبایل و تأیید حذف.
 */
const app = {
    init() {
        this.initCopy();
        this.initMenus();
        this.initConfirm();
        this.initJumpBar();
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
        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-print]');
            if (!trigger) return;

            event.preventDefault();
            window.print();
        });
    },

    /* کپی کردن متن در کلیپ‌بورد: data-copy="متن" یا data-copy-target="#selector" */
    initCopy() {
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
            toggle.addEventListener('click', () => {
                const target = document.querySelector(toggle.getAttribute('data-menu-toggle'));
                target?.classList.toggle('hidden');
            });
        });
    },

    /* تأیید قبل از حذف: data-confirm="متن پرسش" */
    initConfirm() {
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
            chip.addEventListener('click', (event) => {
                const candidates = Array.from(document.querySelectorAll(`[data-jump-anchor="${chip.dataset.jumpAny}"]`));
                const visible = candidates.find((el) => el.offsetParent !== null) || candidates[0];

                if (!visible) return;

                event.preventDefault();
                openAndScroll(visible);
            });
        });

        // اگر آدرس با #day-N باز شود، همان روز باز بماند
        if (window.location.hash) {
            const target = document.querySelector(window.location.hash);

            if (target && target.tagName === 'DETAILS') {
                target.open = true;
            }
        }
    },

    /* بزرگ‌شدن خودکار textarea های فهرست‌ها */
    initAutoGrow() {
        document.querySelectorAll('textarea[data-autogrow]').forEach((area) => {
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
