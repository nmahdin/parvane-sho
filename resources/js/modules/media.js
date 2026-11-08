import { toPersianDigits } from '../jalali-datepicker';

/**
 * رسانه: پخش‌کنندهٔ صوت با بارگذاری هوشمند و ظاهر شدن عکس‌ها از مات به شفاف.
 */
export default {
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

            /* بارگذاری هوشمند: آبجکت صوت تا لحظه‌ای که کاربر دکمهٔ پخش را
               نزده ساخته نمی‌شود (preload: none). این‌طور صوتِ کارت روزی که
               هنوز باز نشده هیچ بایتی از اینترنت کاربر مصرف نمی‌کند و فقط
               موقع پخش، به‌تدریج دانلود می‌شود. */
            let audio = null;

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
                const duration = audio && Number.isFinite(audio.duration) ? audio.duration : 0;
                const time = audio ? audio.currentTime : 0;
                const progress = duration > 0 ? (time / duration) * 100 : 0;

                seek.style.setProperty('--audio-progress', `${progress.toFixed(2)}%`);

                if (!dragging) {
                    seek.value = String(Math.round(progress * 10));
                }

                currentLabel.textContent = toFaTime(time);
                durationLabel.textContent = toFaTime(duration);
            };

            const setPlaying = (playing) => {
                toggle.setAttribute('aria-pressed', playing ? 'true' : 'false');
                toggle.setAttribute('aria-label', `${playing ? 'توقف' : 'پخش'} فایل صوتی ${player.dataset.audioTitle}`);
                icons.play.classList.toggle('hidden', playing);
                icons.pause.classList.toggle('hidden', !playing);
                player.classList.toggle('is-playing', playing);
            };

            const ensureAudio = () => {
                if (audio) return audio;

                audio = new Audio();
                audio.preload = 'none';
                audio.src = player.dataset.audioSrc;
                audio.playbackRate = speeds[speedIndex];

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

                audio.addEventListener('play', () => setPlaying(true));
                audio.addEventListener('pause', () => {
                    localStorage.setItem(
                        'pv-audio-' + (player.dataset.audioId || player.dataset.audioSrc),
                        String(Math.round(audio.currentTime))
                    );
                    setPlaying(false);
                });
                audio.addEventListener('ended', () => {
                    localStorage.removeItem('pv-audio-' + (player.dataset.audioId || player.dataset.audioSrc));
                    audio.currentTime = 0;
                    setPlaying(false);
                    paint();
                });
                audio.addEventListener('loadedmetadata', () => {
                    /* ادامه از جای قبلی: اگر آخرین بار وسط فایل رها شده بود،
                       همان‌جا ادامه بده (نه از اول). */
                    const key = 'pv-audio-' + (player.dataset.audioId || player.dataset.audioSrc);
                    const saved = Number(localStorage.getItem(key) || 0);

                    if (saved > 5 && Number.isFinite(audio.duration) && saved < audio.duration - 5) {
                        audio.currentTime = saved;
                    }

                    paint();
                });

                audio.addEventListener('timeupdate', () => {
                    const key = 'pv-audio-' + (player.dataset.audioId || player.dataset.audioSrc);
                    const now = Date.now();

                    if (now - (player.dataset.lastSave || 0) > 5000) {
                        player.dataset.lastSave = String(now);
                        localStorage.setItem(key, String(Math.round(audio.currentTime)));
                    }
                });
                audio.addEventListener('timeupdate', paint);
                audio.addEventListener('error', () => {
                    toggle.disabled = true;
                    durationLabel.textContent = 'پخش نشد';
                });

                return audio;
            };

            toggle.addEventListener('click', () => {
                const media = ensureAudio();

                if (media.paused) {
                    if (current && current !== media) {
                        current.pause();
                    }

                    current = media;
                    media.play().catch(() => setPlaying(false));
                } else {
                    media.pause();
                }
            });

            seek.addEventListener('pointerdown', () => { dragging = true; });
            seek.addEventListener('pointerup', () => { dragging = false; });

            seek.addEventListener('input', () => {
                const duration = audio && Number.isFinite(audio.duration) ? audio.duration : 0;
                const ratio = Number(seek.value) / 1000;

                seek.style.setProperty('--audio-progress', `${(ratio * 100).toFixed(2)}%`);
                currentLabel.textContent = toFaTime(duration * ratio);
            });

            seek.addEventListener('change', () => {
                if (!audio) return;

                const duration = Number.isFinite(audio.duration) ? audio.duration : 0;

                if (duration > 0) {
                    audio.currentTime = duration * (Number(seek.value) / 1000);
                }

                dragging = false;
                paint();
            });

            speed.addEventListener('click', () => {
                speedIndex = (speedIndex + 1) % speeds.length;

                if (audio) {
                    audio.playbackRate = speeds[speedIndex];
                }

                speed.textContent = toPersianDigits(speeds[speedIndex]) + '×';
            });

            paint();
        });
    },

    /* عکس‌ها هنگام لود از مات به شفاف می‌روند؛ اگر از کش مرورگر
       فوری آماده بودند، بدون انیمیشن شفاف می‌شوند تا معطل نمانند. */
    initImageReveal() {
        document.querySelectorAll('img.img-reveal').forEach((image) => {
            if (image.dataset.revealBound) return;
            image.dataset.revealBound = '1';

            const show = () => image.classList.add('is-loaded');

            if (image.complete && image.naturalWidth > 0) {
                show();
                return;
            }

            image.addEventListener('load', show, { once: true });

            // عکس خراب هرگز پشت مات نمی‌ماند
            image.addEventListener('error', show, { once: true });
        });
    },
};
