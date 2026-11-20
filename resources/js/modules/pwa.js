/**
 * سرویس‌ورکر (کش آفلاین)، حالت تاریک و پوش نوتیفیکیشن یادآور.
 */
export default {
    /* سرویس‌ورکر: کش هوشمند فایل‌های روز (آفلاین) و دریافت پوش نوتیفیکیشن.
       فقط سمت عمومی سایت ثبت می‌شود؛ پنل نیاز به کش آفلاین ندارد. */
    initServiceWorker() {
        if (!('serviceWorker' in navigator)) return;
        if (window.location.pathname.startsWith('/panel')) return;

        navigator.serviceWorker.register('/sw.js').catch(() => {});
    },

    /* حالت تاریک: سوییچ در هدر، ذخیره در localStorage و احترام به سلیقهٔ
       سیستم وقتی کاربر هنوز دستی انتخاب نکرده است. */
    initThemeToggle() {
        const root = document.documentElement;

        const stored = localStorage.getItem('pv-theme');

        if (stored === 'dark' || (!stored && window.matchMedia?.('(prefers-color-scheme: dark)').matches)) {
            root.classList.add('dark');
        }

        document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
            const sync = () => {
                const dark = root.classList.contains('dark');
                button.setAttribute('aria-pressed', dark ? 'true' : 'false');
                button.setAttribute('aria-label', dark ? 'روشن کردن حالت روز' : 'تاریک کردن سایت');
                button.querySelectorAll('[data-theme-icon]').forEach((icon) => {
                    icon.classList.toggle('hidden', icon.dataset.themeIcon !== (dark ? 'sun' : 'moon'));
                });
            };

            sync();

            button.addEventListener('click', () => {
                const dark = root.classList.toggle('dark');
                localStorage.setItem('pv-theme', dark ? 'dark' : 'light');
                sync();
            });
        });
    },

    /* یادآور روزانه با پوش نوتیفیکیشن: دکمهٔ شرکت‌کننده اجازه می‌گیرد،
       در سرویس‌ورکر عضو می‌شود و اشتراک را برای سرور می‌فرستد. */
    initPushReminders() {
        document.querySelectorAll('[data-push-subscribe]').forEach((button) => {
            button.addEventListener('click', async () => {
                const url = button.dataset.pushSubscribe;
                const vapidKey = button.dataset.vapidKey;

                if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                    this.showToast('مرورگرت از پوش نوتیفیکیشن پشتیبانی نمی‌کند.', 'error');
                    return;
                }

                try {
                    const permission = await Notification.requestPermission();

                    if (permission !== 'granted') {
                        this.showToast('بدون اجازهٔ اعلان، یادآوری فعال نمی‌شود.', 'error');
                        return;
                    }

                    const registration = await navigator.serviceWorker.ready;

                    const toUint8 = (base64) => {
                        const padding = '='.repeat((4 - (base64.length % 4)) % 4);
                        const bytes = atob((base64 + padding).replace(/-/g, '+').replace(/_/g, '/'));
                        return Uint8Array.from([...bytes].map((char) => char.charCodeAt(0)));
                    };

                    const subscription = await registration.pushManager.subscribe({
                        userVisibleOnly: true,
                        applicationServerKey: toUint8(vapidKey),
                    });

                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            Accept: 'application/json',
                        },
                        body: JSON.stringify(subscription.toJSON()),
                    });

                    if (response.ok) {
                        this.showToast('یادآور روزانه فعال شد. 🔔', 'success');
                        button.textContent = 'یادآور روزانه فعال است 🔔';
                        button.disabled = true;
                    } else {
                        this.showToast('فعال‌سازی یادآور ناموفق بود.', 'error');
                    }
                } catch (error) {
                    this.showToast('فعال‌سازی یادآور ناموفق بود.', 'error');
                }
            });
        });
    },
};
