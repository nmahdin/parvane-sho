/**
 * مودال‌های پنل: باز/بسته شدن با انیمیشن و پاک‌سازی فرم‌ها.
 */
export default {
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
};
