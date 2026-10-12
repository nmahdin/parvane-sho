/**
 * مدال انتخابگر تاریخ شمسی (بدون وابستگی به فریم‌ورک).
 *
 * روی هر input با ویژگی data-jalali-picker فعال می‌شود، مقدار را به شکل
 * «۱۴۰۵/۰۷/۲۰» در همان input می‌گذارد و با تبدیل jalaali-js کار می‌کند.
 */
import { toJalaali, toGregorian, jalaaliMonthLength, isValidJalaaliDate } from 'jalaali-js';

const MONTHS = [
    'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
    'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند',
];

const WEEKDAYS = ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'];

const FA_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];

const toFa = (value) => String(value).replace(/\d/g, (digit) => FA_DIGITS[Number(digit)]);
const toEn = (value) => String(value)
    .replace(/[۰-۹]/g, (digit) => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(digit)))
    .replace(/[٠-٩]/g, (digit) => String('٠١٢٣٤٥٦٧٨٩'.indexOf(digit)));

/** بخش‌های تاریخ را از مقدار input می‌خواند (شمسی یا میلادی). */
function parseValue(raw) {
    const value = toEn(String(raw ?? '').trim()).replace(/[-.]/g, '/');
    const match = value.match(/^(\d{3,4})\/(\d{1,2})\/(\d{1,2})$/);

    if (!match) {
        return null;
    }

    let [, year, month, day] = match.map(Number);

    if (year < 1700) {
        return isValidJalaaliDate(year, month, day) ? { jy: year, jm: month, jd: day } : null;
    }

    const converted = toJalaali(year, month, day);

    return converted && isValidJalaaliDate(converted.jy, converted.jm, converted.jd) ? converted : null;
}

function today() {
    const now = new Date();

    return toJalaali(now.getFullYear(), now.getMonth() + 1, now.getDate());
}

/** روز هفته شمسی: شنبه = ۰ */
function jalaaliWeekdayOffset(jy, jm) {
    const { gy, gm, gd } = toGregorian(jy, jm, 1);
    const date = new Date(gy, gm - 1, gd);
    // getDay: 0=Sunday … 6=Saturday  →  شنبه باید ۰ شود
    return (date.getDay() + 1) % 7;
}

class JalaliDatePicker {
    constructor(input) {
        this.input = input;
        this.selected = parseValue(input.value) ?? today();
        this.view = { ...this.selected };
        this.build();
        this.bind();
    }

    /* ---------- ساخت مدال ---------- */
    build() {
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'fixed inset-0 z-[70] hidden items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm';
        this.wrapper.setAttribute('role', 'dialog');
        this.wrapper.setAttribute('aria-modal', 'true');

        this.wrapper.innerHTML = `
            <div class="animate-rise w-full max-w-sm rounded-3xl bg-white p-5 shadow-xl ring-1 ring-slate-900/5" data-picker-panel>
                <div class="flex items-center justify-between">
                    <button type="button" class="grid size-9 place-items-center rounded-xl text-slate-500 hover:bg-slate-100" data-nav="prev-year" aria-label="سال قبل">«</button>
                    <button type="button" class="grid size-9 place-items-center rounded-xl text-slate-500 hover:bg-slate-100" data-nav="prev-month" aria-label="ماه قبل">‹</button>

                    <div class="text-center">
                        <p class="text-sm font-semibold text-slate-900" data-picker-title></p>
                        <p class="mt-0.5 text-[11px] text-slate-400">تاریخ شمسی</p>
                    </div>

                    <button type="button" class="grid size-9 place-items-center rounded-xl text-slate-500 hover:bg-slate-100" data-nav="next-month" aria-label="ماه بعد">›</button>
                    <button type="button" class="grid size-9 place-items-center rounded-xl text-slate-500 hover:bg-slate-100" data-nav="next-year" aria-label="سال بعد">»</button>
                </div>

                <div class="mt-4 grid grid-cols-7 gap-1 text-center text-[11px] font-medium text-slate-400">
                    ${WEEKDAYS.map((day) => `<span>${day}</span>`).join('')}
                </div>

                <div class="mt-1 grid grid-cols-7 gap-1" data-picker-days></div>

                <div class="mt-5 flex items-center gap-2 border-t border-slate-100 pt-4">
                    <button type="button" class="btn btn-soft btn-sm flex-1" data-picker-today>امروز</button>
                    <button type="button" class="btn btn-white btn-sm flex-1" data-picker-clear>پاک کردن</button>
                    <button type="button" class="btn btn-primary btn-sm flex-1" data-picker-close>بستن</button>
                </div>
            </div>
        `;

        document.body.appendChild(this.wrapper);

        this.title = this.wrapper.querySelector('[data-picker-title]');
        this.days = this.wrapper.querySelector('[data-picker-days]');
    }

    bind() {
        this.input.setAttribute('autocomplete', 'off');
        this.input.setAttribute('inputmode', 'numeric');
        this.input.addEventListener('focus', () => this.open());
        this.input.addEventListener('click', () => this.open());

        this.wrapper.addEventListener('click', (event) => {
            if (event.target === this.wrapper || event.target.closest('[data-picker-close]')) {
                this.close();

                return;
            }

            const nav = event.target.closest('[data-nav]');

            if (nav) {
                this.navigate(nav.getAttribute('data-nav'));

                return;
            }

            if (event.target.closest('[data-picker-today]')) {
                this.pick(today());
                this.close();

                return;
            }

            if (event.target.closest('[data-picker-clear]')) {
                this.input.value = '';
                this.input.dispatchEvent(new Event('change', { bubbles: true }));
                this.close();

                return;
            }

            const day = event.target.closest('[data-day]');

            if (day) {
                const jy = Number(day.getAttribute('data-jy'));
                const jm = Number(day.getAttribute('data-jm'));
                const jd = Number(day.getAttribute('data-day'));

                this.pick({ jy, jm, jd });
                this.close();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !this.wrapper.classList.contains('hidden')) {
                this.close();
            }
        });

        // اگر کاربر تاریخ را دستی تایپ کرد، نمایش مدال را با آن هم‌تراز می‌کنیم
        this.input.addEventListener('change', () => {
            const parsed = parseValue(this.input.value);

            if (parsed) {
                this.selected = parsed;
                this.view = { ...parsed };
            }
        });
    }

    /* ---------- رفتار ---------- */
    open() {
        const parsed = parseValue(this.input.value);

        if (parsed) {
            this.selected = parsed;
            this.view = { ...parsed };
        }

        this.render();
        this.wrapper.classList.remove('hidden');
        this.wrapper.classList.add('flex');
    }

    close() {
        this.wrapper.classList.add('hidden');
        this.wrapper.classList.remove('flex');
    }

    navigate(direction) {
        const deltas = {
            'prev-year': [-1, 0],
            'next-year': [1, 0],
            'prev-month': [0, -1],
            'next-month': [0, 1],
        };

        const [yearDelta, monthDelta] = deltas[direction] ?? [0, 0];

        let month = this.view.jm + monthDelta;
        let year = this.view.jy + yearDelta;

        if (month < 1) {
            month = 12;
            year -= 1;
        } else if (month > 12) {
            month = 1;
            year += 1;
        }

        this.view = { jy: year, jm: month, jd: 1 };
        this.render();
    }

    pick({ jy, jm, jd }) {
        this.selected = { jy, jm, jd };
        this.view = { jy, jm, jd };
        this.input.value = toFa(`${jy}/${String(jm).padStart(2, '0')}/${String(jd).padStart(2, '0')}`);
        this.input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    render() {
        const { jy, jm } = this.view;
        const length = jalaaliMonthLength(jy, jm);
        const offset = jalaaliWeekdayOffset(jy, jm);
        const now = today();
        const cells = [];

        for (let i = 0; i < offset; i++) {
            cells.push('<span></span>');
        }

        for (let day = 1; day <= length; day++) {
            const isSelected = this.selected.jy === jy && this.selected.jm === jm && this.selected.jd === day;
            const isToday = now.jy === jy && now.jm === jm && now.jd === day;

            const classes = isSelected
                ? 'bg-brand-600 font-semibold text-white'
                : isToday
                    ? 'bg-brand-50 font-semibold text-brand-700 ring-1 ring-brand-200'
                    : 'text-slate-700 hover:bg-slate-100';

            cells.push(`
                <button type="button" data-day="${day}" data-jy="${jy}" data-jm="${jm}"
                        class="grid h-10 w-full place-items-center rounded-xl text-sm transition ${classes}">
                    ${toFa(day)}
                </button>
            `);
        }

        this.title.textContent = `${MONTHS[jm - 1]} ${toFa(jy)}`;
        this.days.innerHTML = cells.join('');
    }
}

export function initJalaliPickers(root = document) {
    root.querySelectorAll('input[data-jalali-picker]').forEach((input) => {
        if (!input.dataset.pickerReady) {
            input.dataset.pickerReady = '1';
            new JalaliDatePicker(input);
        }
    });
}

export default JalaliDatePicker;
