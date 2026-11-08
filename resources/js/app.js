/**
 * نقطهٔ ورود اسکریپت‌های سایت — هر بخش در پوشهٔ modules جدا شده است:
 * panel-forms (فرم‌های پنل)، ui (اجزای رابط)، media (صوت و عکس)،
 * pwa (سرویس‌ورکر/تم/پوش)، charts (نمودارها)، ajax-save (ذخیرهٔ بی‌رفرش)
 * و modals (مودال‌ها). همه روی یک آبجکت app ادغام می‌شوند.
 */
import ajaxSave from './modules/ajax-save';
import charts from './modules/charts';
import media from './modules/media';
import modals from './modules/modals';
import panelForms from './modules/panel-forms';
import pwa from './modules/pwa';
import ui from './modules/ui';

const app = {
    init() {
        this.initFlashToasts();
        this.initAjaxSave();
        this.initUploadProgress();
        this.initModals();
        this.initDayViewTracking();
        this.initCustomCharts();
        this.initStickyOffsets();
        this.initCardEntrance();
        this.initDayCardAnimation();
        this.initPageLoader();
        this.initPageReadyChip();
        this.initActionMenus();
        this.initCustomSelects();
        this.initCopy();
        this.initMenus();
        this.initConfirm();
        this.initJumpBar();
        this.initAudioPlayers();
        this.initImageReveal();
        this.initServiceWorker();
        this.initThemeToggle();
        this.initPushReminders();
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

    ...panelForms,
    ...ui,
    ...media,
    ...pwa,
    ...charts,
    ...ajaxSave,
    ...modals,
};

document.addEventListener('DOMContentLoaded', () => app.init());
