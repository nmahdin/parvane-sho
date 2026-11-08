/* پروانه‌شو — Service Worker
   ۱) کش هوشمند فایل‌های روز (صوت/تصویر/فایل): هر فایلی که یک بار گرفته شد،
      دفعهٔ بعد بدون اینترنت هم باز/پخش می‌شود.
   ۲) دریافت پوش‌نوتیفیکیشن یادآور روزانه. */

const CACHE = 'parvane-files-v1';
const MAX_ENTRIES = 60;

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    if (event.request.method !== 'GET' || url.origin !== self.location.origin) return;

    // فقط فایل‌های روز (صوت و ضمیمه) کش می‌شوند؛ صفحات همیشه از سرور
    if (!url.pathname.includes('/day-files/')) return;

    event.respondWith(
        caches.open(CACHE).then(async (cache) => {
            const cached = await cache.match(event.request);
            if (cached) return cached;

            try {
                const response = await fetch(event.request);

                if (response.ok || response.status === 206) {
                    cache.put(event.request, response.clone()).then(async () => {
                        const keys = await cache.keys();
                        if (keys.length > MAX_ENTRIES) {
                            await cache.delete(keys[0]);
                        }
                    });
                }

                return response;
            } catch (error) {
                const offline = await cache.match(event.request);
                if (offline) return offline;
                throw error;
            }
        })
    );
});

/* یادآور روزانه: پوش با پیام خالی می‌آید و متن از همین‌جا نشان داده می‌شود */
self.addEventListener('push', (event) => {
    let data = {};

    try {
        if (event.data) data = event.data.json();
    } catch (error) {
        data = {};
    }

    event.waitUntil(
        self.registration.showNotification(data.title || 'پروانه‌شو', {
            body: data.body || 'کارهای امروزت منتظرته! 🌱 چند دقیقه وقت بگذار و روزت را کامل کن.',
            icon: '/favicon.svg',
            badge: '/favicon.svg',
            dir: 'rtl',
            lang: 'fa',
            tag: 'daily-reminder',
            data: { url: data.url || '/code' },
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            const target = event.notification.data?.url || '/code';

            for (const client of clients) {
                if (client.url.includes(target) && 'focus' in client) return client.focus();
            }

            return self.clients.openWindow(target);
        })
    );
});
