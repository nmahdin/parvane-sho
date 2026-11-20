#!/usr/bin/env bash
#
# بالا آوردن کامل محیط توسعه/پیش‌نمایش با یک فرمان (idempotent).
#
# محیط سندباکس گاهی ریست می‌شود و PHP، فایل .env، دیتابیس، node_modules و
# دارایی‌های بیلدشده از بین می‌روند؛ در آن حالت صفحهٔ پیش‌نمایش بالا نمی‌آید و
# هر آپلودی با «ذخیره نشد» شکست می‌خورد (چون اساساً سروری پاسخ نمی‌دهد).
# این اسکریپت هر چه لازم است را دوباره می‌سازد:
#
#   bash scripts/preview-up.sh          # بازسازی محیط
#   bash scripts/preview-up.sh serve    # بازسازی + اجرای سرور روی پورت ۸۰۰۰
#
set -euo pipefail

cd "$(dirname "$0")/.."
ROOT="$(pwd)"

log() { printf '\033[1;32m==>\033[0m %s\n' "$1"; }

# ── ۱) باینری PHP (اگر نبود، از بستهٔ NativePHP بگیر) ──────────────────────
if ! command -v php >/dev/null 2>&1; then
    log "نصب PHP 8.4"
    mkdir -p /tmp/phpzip
    curl -sL -H "Accept: application/vnd.github.raw" \
        -o /tmp/phpzip/php.zip \
        "https://api.github.com/repos/NativePHP/php-bin/contents/bin/linux/x64/php-8.4.zip?ref=main"
    (cd /tmp/phpzip && unzip -o -q php.zip && install -m 0755 php /usr/local/bin/php)
fi
php -v | head -1

# ── ۲) سقف آپلود: ۶۰ مگابایت واقعی (وگرنه PHP خودش درخواست را قطع می‌کند) ──
if ! php -i 2>/dev/null | grep -q '^upload_max_filesize => 64M'; then
    log "تنظیم upload_max_filesize=64M و post_max_size=70M"
    mkdir -p /usr/local/etc/php/conf.d
    cat > /usr/local/etc/php/conf.d/99-upload.ini <<'INI'
upload_max_filesize = 64M
post_max_size = 70M
memory_limit = 256M
max_execution_time = 300
INI
fi

# ── ۳) .env و دیتابیس sqlite ───────────────────────────────────────────────
if [ ! -f .env ]; then
    log "ساخت .env"
    cp .env.example .env
    sed -i "s|^DB_CONNECTION=.*|DB_CONNECTION=sqlite|;
            s|^#\? *DB_DATABASE=.*|DB_DATABASE=${ROOT}/database/database.sqlite|;
            s|^APP_URL=.*|APP_URL=http://localhost:8000|" .env
    php artisan key:generate --force >/dev/null
fi

# حالت دیباگ: پیش‌فرض روشن است تا خطای ۵۰۰ متن خودش را در توست بگوید.
# برای همانندسازی هاست واقعی (که دیباگ خاموش است):  PREVIEW_DEBUG=0 bash scripts/preview-up.sh
# در هر دو حالت علت خطا در storage/logs/laravel.log ثبت و از
# «مدیریت منابع ← لاگ خطاها» خوانده می‌شود.
WANT_DEBUG="$([ "${PREVIEW_DEBUG:-1}" = "0" ] && echo false || echo true)"

if ! grep -q "^APP_DEBUG=${WANT_DEBUG}" .env; then
    log "APP_DEBUG=${WANT_DEBUG}"
    sed -i "s|^APP_DEBUG=.*|APP_DEBUG=${WANT_DEBUG}|" .env
fi
php artisan config:clear >/dev/null 2>&1 || true

touch database/database.sqlite
php artisan migrate --force >/dev/null

if [ "$(php artisan tinker --execute='echo App\Models\Challenge::count();' 2>/dev/null | tr -d '[:space:]')" = "0" ]; then
    log "ساخت دادهٔ نمونه"
    php artisan db:seed --force >/dev/null
fi

# ── ۳.۵) ستون is_video باید وجود داشته باشد؛ بدون آن هر آپلود فایلی ۵۰۰ می‌شود
if [ "$(php artisan tinker --execute='echo (int) Schema::hasColumn("day_files", "is_video");' 2>/dev/null | tr -d '[:space:]')" != "1" ]; then
    log "ستون is_video نیست — اجرای دوبارهٔ مهاجرت‌ها"
    php artisan migrate --force
fi

# ── ۴) وابستگی‌های node (بایندینگ‌های نیتیو گاهی نصب نمی‌شوند) ─────────────
if [ ! -d node_modules/vite ]; then
    log "npm install"
    npm install --no-audit --no-fund >/dev/null
fi

if [ ! -d node_modules/@rolldown/binding-linux-x64-gnu ] || [ ! -d node_modules/lightningcss-linux-x64-gnu ]; then
    log "نصب بایندینگ‌های نیتیو rolldown و lightningcss"
    npm i --no-save --no-audit --no-fund \
        "@rolldown/binding-linux-x64-gnu@$(node -p "require('./node_modules/rolldown/package.json').version")" \
        "lightningcss-linux-x64-gnu@$(node -p "require('./node_modules/lightningcss/package.json').version")" >/dev/null
fi

# ── ۵) دارایی‌ها ────────────────────────────────────────────────────────────
log "بیلد دارایی‌ها"
php artisan view:clear >/dev/null
npm run build >/dev/null
php artisan view:cache >/dev/null

log "آماده است."

if [ "${1:-}" = "serve" ]; then
    log "اجرای سرور روی 0.0.0.0:8000"
    exec php artisan serve --host=0.0.0.0 --port=8000
fi
