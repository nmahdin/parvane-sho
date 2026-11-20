import { toPersianDigits } from '../jalali-datepicker';

/**
 * نمودارهای سفارشی SVG (میله‌ای، خطی و دونات) برای آمار.
 */
export default {
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
};
