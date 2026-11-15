import { toPersianDigits } from '../jalali-datepicker';

/* در هر لحظه فقط یک رسانه (صوت یا ویدیو) پخش می‌شود. */
let currentMedia = null;

const playExclusive = (media) => {
    if (currentMedia && currentMedia !== media) {
        currentMedia.pause();
    }

    currentMedia = media;
};

/**
 * رسانه: پخش‌کنندهٔ اختصاصی صوت و ویدیو با بارگذاری هوشمند و ظاهر شدن
 * عکس‌ها از مات به شفاف.
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
                    playExclusive(media);
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

    /* پخش‌کننده اختصاصی ویدیو: همان قاعده‌های صوت (بارگذاری هوشمند،
       «فقط یک رسانه پخش شود»، ادامه از جای قبلی) به‌علاوهٔ بی‌صدا،
       تمام‌صفحه و نشانگر پرشدن حافظه. کنترل‌های پیش‌فرض مرورگر خاموش است. */
    initVideoPlayers() {
        const toFaTime = (seconds) => {
            if (!Number.isFinite(seconds) || seconds < 0) seconds = 0;

            const mins = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            const pad = (value) => String(value).padStart(2, '0');

            return toPersianDigits(`${pad(mins)}:${pad(secs)}`);
        };

        const players = Array.from(document.querySelectorAll('[data-video-player]'));
        if (!players.length) return;

        players.forEach((player) => {
            if (player.dataset.bound) return;
            player.dataset.bound = '1';

            const stage = player.querySelector('[data-video-stage]');
            const toggles = Array.from(player.querySelectorAll('[data-video-toggle]'));
            const seek = player.querySelector('[data-video-seek]');
            const speed = player.querySelector('[data-video-speed]');
            const mute = player.querySelector('[data-video-mute]');
            const fullscreen = player.querySelector('[data-video-fullscreen]');
            const currentLabel = player.querySelector('[data-video-current]');
            const durationLabel = player.querySelector('[data-video-duration]');
            const buffering = player.querySelector('[data-video-buffering]');
            const iconSet = (name) => Array.from(player.querySelectorAll(`[data-video-icon="${name}"]`));
            const resumeKey = 'pv-video-' + (player.dataset.videoId || player.dataset.videoSrc);

            const speeds = [1, 1.25, 1.5, 0.75];
            let speedIndex = 0;
            let dragging = false;
            let loaded = false;

            const paint = () => {
                const duration = Number.isFinite(stage.duration) ? stage.duration : 0;
                const time = stage.currentTime || 0;
                const progress = duration > 0 ? (time / duration) * 100 : 0;

                seek.style.setProperty('--video-progress', `${progress.toFixed(2)}%`);

                if (!dragging) {
                    seek.value = String(Math.round(progress * 10));
                }

                currentLabel.textContent = toFaTime(time);
                durationLabel.textContent = toFaTime(duration);
            };

            const setPlaying = (playing) => {
                player.classList.toggle('is-playing', playing);
                buffering.classList.toggle('hidden', !playing);

                toggles.forEach((toggle) => {
                    toggle.setAttribute('aria-pressed', playing ? 'true' : 'false');
                    toggle.setAttribute('aria-label', `${playing ? 'توقف' : 'پخش'} ویدیوی ${player.dataset.videoTitle}`);
                });

                iconSet('play').forEach((icon) => icon.classList.toggle('hidden', playing));
                iconSet('pause').forEach((icon) => icon.classList.toggle('hidden', !playing));
            };

            const setMuted = (muted) => {
                stage.muted = muted;
                mute.setAttribute('aria-pressed', muted ? 'true' : 'false');
                mute.setAttribute('aria-label', muted ? 'با صدا کردن ویدیو' : 'بی‌صدا کردن ویدیو');
                iconSet('sound-on').forEach((icon) => icon.classList.toggle('hidden', muted));
                iconSet('sound-off').forEach((icon) => icon.classList.toggle('hidden', !muted));
            };

            /* بارگذاری هوشمند: تا پیش از اولین پخش، src روی تگ ویدیو گذاشته
               نمی‌شود؛ پس ویدیوی ۶۰ مگابایتی کارتِ روزی که هنوز باز نشده هیچ
               بایتی از اینترنت کاربر نمی‌گیرد و موقع پخش تکه‌تکه (Range)
               دانلود می‌شود. */
            const ensureSource = () => {
                if (loaded) return stage;
                loaded = true;

                stage.src = player.dataset.videoSrc;
                stage.playbackRate = speeds[speedIndex];

                return stage;
            };

            // آمار دقیق پخش: فقط وقتی کاربر واقعاً دکمهٔ پخش را بزند.
            // در پنل (پیش‌نمایش مدیر) شمرده نمی‌شود.
            stage.addEventListener('play', () => {
                if (player.dataset.playSent) return;
                player.dataset.playSent = '1';

                if (!player.dataset.videoPlayUrl || window.location.pathname.startsWith('/panel')) return;

                const body = new FormData();
                body.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');

                fetch(player.dataset.videoPlayUrl, {
                    method: 'POST',
                    body,
                    keepalive: true,
                }).catch(() => {});
            });

            stage.addEventListener('play', () => setPlaying(true));
            stage.addEventListener('pause', () => {
                localStorage.setItem(resumeKey, String(Math.round(stage.currentTime)));
                setPlaying(false);
            });
            stage.addEventListener('ended', () => {
                localStorage.removeItem(resumeKey);
                stage.currentTime = 0;
                setPlaying(false);
                paint();
            });
            stage.addEventListener('loadedmetadata', () => {
                /* ادامه از جای قبلی: اگر آخرین بار وسط ویدیو رها شده بود،
                   همان‌جا ادامه بده (نه از اول). */
                const saved = Number(localStorage.getItem(resumeKey) || 0);

                if (saved > 5 && Number.isFinite(stage.duration) && saved < stage.duration - 5) {
                    stage.currentTime = saved;
                }

                paint();
            });
            stage.addEventListener('timeupdate', () => {
                const now = Date.now();

                if (now - (player.dataset.lastSave || 0) > 5000) {
                    player.dataset.lastSave = String(now);
                    localStorage.setItem(resumeKey, String(Math.round(stage.currentTime)));
                }
            });
            stage.addEventListener('timeupdate', paint);
            stage.addEventListener('waiting', () => buffering.classList.remove('hidden'));
            stage.addEventListener('playing', () => buffering.classList.toggle('hidden', stage.paused));
            stage.addEventListener('error', () => {
                toggles.forEach((toggle) => { toggle.disabled = true; });
                buffering.classList.add('hidden');
                durationLabel.textContent = 'پخش نشد';
            });

            const togglePlay = () => {
                const media = ensureSource();

                if (media.paused) {
                    playExclusive(media);
                    media.play().catch(() => setPlaying(false));
                } else {
                    media.pause();
                }
            };

            toggles.forEach((toggle) => toggle.addEventListener('click', togglePlay));

            seek.addEventListener('pointerdown', () => { dragging = true; });
            seek.addEventListener('pointerup', () => { dragging = false; });

            seek.addEventListener('input', () => {
                const duration = Number.isFinite(stage.duration) ? stage.duration : 0;
                const ratio = Number(seek.value) / 1000;

                seek.style.setProperty('--video-progress', `${(ratio * 100).toFixed(2)}%`);
                currentLabel.textContent = toFaTime(duration * ratio);
            });

            seek.addEventListener('change', () => {
                if (!loaded) return;

                const duration = Number.isFinite(stage.duration) ? stage.duration : 0;

                if (duration > 0) {
                    stage.currentTime = duration * (Number(seek.value) / 1000);
                }

                dragging = false;
                paint();
            });

            speed.addEventListener('click', () => {
                speedIndex = (speedIndex + 1) % speeds.length;
                stage.playbackRate = speeds[speedIndex];
                speed.textContent = toPersianDigits(speeds[speedIndex]) + '×';
            });

            mute.addEventListener('click', () => setMuted(!stage.muted));

            fullscreen.addEventListener('click', () => {
                if (document.fullscreenElement) {
                    document.exitFullscreen?.().catch(() => {});

                    return;
                }

                player.requestFullscreen?.().catch(() => {});
            });

            setMuted(false);
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
