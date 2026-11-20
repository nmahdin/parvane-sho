/**
 * اجزای رابط کاربری: منوها، کپی، تأیید حذف، نوار پرش، انیمیشن کارت‌ها،
   انتخابگرهای سفارشی، چیپ‌ها، لودر صفحه و رهگیری بازدید روز.
 */
export default {
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

    /* انیمیشن ورود کارت‌ها: هر کارت کمی دیرتر از قبلی ظاهر می‌شود.
       بعد از رفرش جزئی بخش‌ها هم کارت‌های تازه همان‌طور می‌آیند. */
    initCardEntrance() {
        document.querySelectorAll('.card, .day-card, .day-group, .surface').forEach((el, index) => {
            if (el.dataset.entered) return;
            el.dataset.entered = '1';
            el.style.animationDelay = `${Math.min(index, 10) * 45}ms`;
            // انیمیشن از ابتدا «مکث» بود؛ حالا با تأخیر درست پخش می‌شود
            el.style.animationPlayState = 'running';
        });
    },

    /* هدر چسبان کمی از بالا فاصله دارد؛ نوار ناوبری چسبان باید دقیقاً
       زیر هدر بنشیند — ارتفاع واقعی هدر اندازه‌گیری و در متغیر CSS گذاشته می‌شود. */
    initStickyOffsets() {
        const header = document.getElementById('panel-header');
        const nav = document.querySelector('.sticky-nav');
        if (!header || !nav) return;

        const update = () => {
            const gap = 12; // همان top-3 هدر
            document.documentElement.style.setProperty(
                '--sticky-nav-top',
                `${header.offsetHeight + gap * 2}px`
            );
        };

        update();

        // با اسکرول، هدر از نیمه‌شفاف به کاملاً مات (و سایه‌دار) می‌رود
        const stuck = () => {
            header.classList.toggle('is-stuck', window.scrollY > 8);
        };

        stuck();

        if (!this._stickyOffsetsBound) {
            this._stickyOffsetsBound = true;

            let timer;
            window.addEventListener('resize', () => {
                window.clearTimeout(timer);
                timer = window.setTimeout(update, 120);
            });

            window.addEventListener('scroll', stuck, { passive: true });

            if (document.fonts?.ready) {
                document.fonts.ready.then(update);
            }
        }
    },

    /* انتخابگر اختصاصی: منوی پیش‌فرض مرورگر با فهرست سبک سایت جایگزین
       می‌شود؛ خود <select> پنهان می‌ماند تا فرم‌ها مثل قبل کار کنند. */
    initCustomSelects() {
        const chevron = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-4"><path d="M6 9l6 6 6-6"/></svg>';
        const check = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="csel-check size-3.5"><path d="M5 12l5 5 9-11"/></svg>';

        document.querySelectorAll('select.input').forEach((select) => {
            if (select.dataset.cselBound) return;
            select.dataset.cselBound = '1';

            const wrap = document.createElement('div');
            wrap.className = 'csel';
            select.after(wrap);
            wrap.appendChild(select);
            select.classList.add('csel-native');

            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'csel-btn';
            button.setAttribute('aria-haspopup', 'listbox');
            button.setAttribute('aria-expanded', 'false');
            button.innerHTML = '<span class="csel-label"></span>' + chevron;

            const label = button.querySelector('.csel-label');
            const syncLabel = () => {
                label.textContent = select.options[select.selectedIndex]?.textContent.trim() || '';
            };
            syncLabel();

            const list = document.createElement('div');
            list.className = 'csel-list';
            list.setAttribute('role', 'listbox');

            wrap.append(button, list);

            const close = () => {
                wrap.classList.remove('is-open');
                button.setAttribute('aria-expanded', 'false');
            };

            const open = () => {
                document.querySelectorAll('.csel.is-open').forEach((other) => {
                    if (other !== wrap) other.classList.remove('is-open');
                });

                list.innerHTML = '';

                Array.from(select.options).forEach((option) => {
                    const item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'csel-option' + (option.value === select.value ? ' is-selected' : '');
                    item.setAttribute('role', 'option');
                    item.innerHTML = '<span></span>' + (option.value === select.value ? check : '');
                    item.querySelector('span').textContent = option.textContent.trim();

                    item.addEventListener('click', () => {
                        select.value = option.value;
                        syncLabel();
                        close();
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                    });

                    item.addEventListener('keydown', (event) => {
                        if (event.key === 'ArrowDown') {
                            event.preventDefault();
                            item.nextElementSibling?.focus();
                        } else if (event.key === 'ArrowUp') {
                            event.preventDefault();
                            (item.previousElementSibling || button).focus();
                        }
                    });

                    list.appendChild(item);
                });

                wrap.classList.add('is-open');
                button.setAttribute('aria-expanded', 'true');
                list.querySelector('.csel-option')?.focus();
            };

            button.addEventListener('click', (event) => {
                event.stopPropagation();

                if (wrap.classList.contains('is-open')) {
                    close();
                } else {
                    open();
                }
            });

            button.addEventListener('keydown', (event) => {
                if (['Enter', ' ', 'ArrowDown'].includes(event.key)) {
                    event.preventDefault();
                    open();
                }
            });

            document.addEventListener('click', (event) => {
                if (!wrap.contains(event.target)) close();
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') close();
            });
        });
    },

    /* چیپ «در حال بارگذاری…» در صفحه نمایش اطلاعات (صفحه عمومی چالش) */
    initPageReadyChip() {
        const chip = document.getElementById('page-loading-chip');
        if (!chip) return;

        const hide = () => {
            chip.classList.add('is-out');
            setTimeout(() => chip.remove(), 320);
        };

        if (document.readyState === 'complete') {
            hide();
            return;
        }

        chip.hidden = false;
        window.addEventListener('load', hide, { once: true });
        // اطمینان: حتی اگر منبعی گیر کرد، چیپ برای همیشه نماند
        setTimeout(hide, 8000);
    },

    /* منوی عملیات: پنل با position:fixed روی همه‌چیز (از جمله جدول با
       scroll) شناور می‌ماند؛ با کلیک بیرون، Escape یا اسکرول بسته می‌شود. */
    initActionMenus() {
        const closeAll = (except = null) => {
            document.querySelectorAll('details.action-menu[open]').forEach((menu) => {
                if (menu !== except) menu.open = false;
            });
        };

        const place = (menu) => {
            const panel = menu._panel;
            const summary = menu.querySelector('summary');
            if (!panel || !summary) return;

            const rect = summary.getBoundingClientRect();
            panel.style.position = 'fixed';
            panel.style.insetInlineEnd = 'auto';
            panel.style.bottom = 'auto';
            panel.style.left = 'auto';
            panel.style.right = `${Math.max(8, window.innerWidth - rect.right)}px`;

            const height = panel.offsetHeight || 220;
            let top = rect.bottom + 6;

            if (top + height > window.innerHeight - 8) {
                top = Math.max(8, rect.top - height - 6);
            }

            panel.style.top = `${top}px`;
        };

        document.querySelectorAll('details.action-menu').forEach((menu) => {
            if (menu.dataset.menuBound) return;
            menu.dataset.menuBound = '1';
            menu._panel = menu.querySelector('.action-menu-panel');

            menu.addEventListener('toggle', () => {
                if (menu.open) {
                    // پنل از دل جدول (با scroll) به body منتقل می‌شود تا
                    // هیچ overflow یا جدِّ دارای transform نتواند بریده‌اش کند
                    if (menu._panel && menu._panel.parentElement !== document.body) {
                        document.body.appendChild(menu._panel);
                    }

                    place(menu);
                } else if (menu._panel && menu._panel.parentElement === document.body) {
                    menu.appendChild(menu._panel);
                }
            });
        });

        if (this._menusDocumentBound) return;
        this._menusDocumentBound = true;

        document.addEventListener('click', (event) => {
            const menu = event.target.closest('details.action-menu');
            closeAll(menu);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeAll();
        });

        // با اسکرول/رزایز منو دوباره روی دکمه‌اش می‌نشیند (بسته نمی‌شود)
        const reposition = () => {
            const open = document.querySelector('details.action-menu[open]');
            if (open) place(open);
        };

        window.addEventListener('scroll', reposition, { capture: true, passive: true });
        window.addEventListener('resize', reposition);
    },

    /* نوار بارگذاری هنگام جابه‌جایی بین صفحات (لینک‌ها و فرم‌های غیر AJAX) */
    initPageLoader() {
        let showTimer = null;

        const show = () => {
            if (document.getElementById('page-loader')) return;
            const bar = document.createElement('div');
            bar.id = 'page-loader';
            bar.setAttribute('aria-hidden', 'true');
            document.body.appendChild(bar);
        };

        const hide = () => {
            clearTimeout(showTimer);
            document.getElementById('page-loader')?.remove();
        };

        document.addEventListener('click', (event) => {
            if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            const link = event.target.closest('a[href]');
            if (!link || link.target === '_blank' || link.hasAttribute('download')) return;

            const href = link.getAttribute('href') || '';
            if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:')) return;

            const url = new URL(link.href, window.location.href);
            if (url.origin !== window.location.origin) return;

            clearTimeout(showTimer);
            showTimer = setTimeout(show, 180);
        });

        document.addEventListener('submit', (event) => {
            const form = event.target;
            if (form.hasAttribute('data-ajax-save') || form.hasAttribute('data-stay-open')) return;
            clearTimeout(showTimer);
            showTimer = setTimeout(show, 180);
        });

        // بازگشت از bfcache یا لغو ناوبری
        window.addEventListener('pageshow', hide);
        window.addEventListener('pagehide', hide);
    },

    /* باز و بسته شدن نرم کارت روزها و گروه‌های داخلشان: ارتفاع بین
       «فقط سرِ ردیف» و «ارتفاع کامل» انیمیشن می‌شود و محتوا محو/نمایان می‌شود. */
    initDayCardAnimation() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        document.querySelectorAll('details.day-card, details.day-group').forEach((card) => {
            if (card.dataset.animBound) return;
            card.dataset.animBound = '1';

            const summary = card.querySelector(':scope > summary');
            const body = card.querySelector(':scope > .day-body');
            if (!summary || typeof card.animate !== 'function') return;

            const easing = 'cubic-bezier(0.22, 0.8, 0.36, 1)';
            let running = null;

            const stop = () => {
                running = null;
                card.style.height = '';
            };

            summary.addEventListener('click', (event) => {
                // کلیک روی دکمه/لینک/فیلد داخل سرِ ردیف (مثل دکمه‌های افزودن)
                // کار خودش را بکند؛ اینجا فقط باز/بستهٔ خودِ ردیف انیمیشن می‌گیرد
                if (event.target.closest('button, a, input, select, textarea, label')) return;

                event.preventDefault();

                const currentHeight = card.getBoundingClientRect().height;
                const closedHeight = summary.getBoundingClientRect().height;
                if (running) {
                    running.cancel();
                    running = null;
                }

                if (card.open) {
                    running = card.animate(
                        [{ height: `${currentHeight}px` }, { height: `${closedHeight}px` }],
                        { duration: 300, easing }
                    );
                    body?.animate([{ opacity: 1 }, { opacity: 0 }], { duration: 160, easing: 'ease-in' });
                    running.onfinish = () => {
                        card.open = false;
                        stop();
                    };
                } else {
                    card.open = true;
                    const openHeight = Math.max(card.scrollHeight, closedHeight + 1);
                    running = card.animate(
                        [{ height: `${closedHeight}px` }, { height: `${openHeight}px` }],
                        { duration: 340, easing }
                    );
                    body?.animate(
                        [{ opacity: 0, transform: 'translateY(-6px)' }, { opacity: 1, transform: 'none' }],
                        { duration: 300, easing: 'ease-out' }
                    );
                    running.onfinish = stop;
                }

                running.oncancel = stop;
            });
        });
    },

    initDayViewTracking() {
        document.querySelectorAll('details.day-card[data-day-view-url]').forEach((card) => {
            const day = parseInt(card.getAttribute('data-day-view-number') || '0', 10);
            if (!day) return;

            const send = () => {
                const key = `dayview:${card.getAttribute('data-day-view-url')}:${day}`;

                if (sessionStorage.getItem(key)) return;
                sessionStorage.setItem(key, '1');

                const body = new FormData();
                body.append('_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
                body.append('day_number', String(day));

                fetch(card.getAttribute('data-day-view-url'), {
                    method: 'POST',
                    body,
                    keepalive: true,
                }).catch(() => {});
            };

            // کارت روز جاری از قبل باز است — همان باز شدن اول هم یک بازدید است
            if (card.open) send();

            card.addEventListener('toggle', () => {
                if (card.open) send();
            });
        });
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
