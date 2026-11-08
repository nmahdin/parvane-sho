/**
 * ذخیرهٔ بدون رفرش فرم‌ها، نوار پیشرفت آپلود و toastهای پیام.
 */
export default {
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

    /* پیام‌های فلش سرور (ذخیره شد، خوش‌آمدگویی، خطا) به‌جای بنر بالای
       صفحه، به‌صورت توست نشان داده می‌شوند */
    initFlashToasts() {
        const tag = document.getElementById('flash-data');
        if (!tag || tag.dataset.toastShown) return;
        tag.dataset.toastShown = '1';

        let messages = [];
        try {
            messages = JSON.parse(tag.textContent) || [];
        } catch {
            messages = [];
        }

        // اگر خطایی هست اولویت با آن است، وگرنه اولین پیام
        const first = messages.find((item) => item.type !== 'status') || messages[0];

        if (first?.text) {
            this.showToast(first.text, first.type === 'status' ? 'status' : 'error');
        }

        tag.remove();
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

        let message = null;
        const flashTag = parsed.getElementById('flash-data');

        if (flashTag) {
            try {
                const items = JSON.parse(flashTag.textContent) || [];
                const first = items.find((item) => item.type !== 'status') || items[0];
                message = first?.text || null;
            } catch {
                message = null;
            }
        }

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
};
