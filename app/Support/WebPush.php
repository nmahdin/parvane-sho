<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * ارسال پوش نوتیفیکیشن استاندارد وب (Web Push) بدون وابستگی بیرونی:
 * کلیدهای VAPID با openssl ساخته می‌شوند، JWT با ES256 امضا می‌شود و
 * بدنه پیام مطابق RFC 8291 (aes128gcm) رمزنگاری می‌شود.
 */
class WebPush
{
    /**
     * کلید عمومی VAPID (base64url) — همین کلید در مرورگر برای
     * pushManager.subscribe استفاده می‌شود. در نبود، یک‌بار ساخته و
     * در تنظیمات ذخیره می‌شود.
     */
    public static function publicKey(): string
    {
        static::ensureKeys();

        return (string) Setting::get('vapid_public');
    }

    /**
     * ساخت و ذخیرهٔ جفت کلید VAPID در اولین استفاده.
     */
    public static function ensureKeys(): void
    {
        if (Setting::get('vapid_private') && Setting::get('vapid_public')) {
            return;
        }

        $key = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);

        if (! $key) {
            throw new RuntimeException('ساخت کلید VAPID ناموفق بود.');
        }

        openssl_pkey_export($key, $privatePem);

        $details = openssl_pkey_get_details($key);

        Setting::query()->updateOrCreate(
            ['key' => 'vapid_private'],
            ['value' => $privatePem]
        );

        Setting::query()->updateOrCreate(
            ['key' => 'vapid_public'],
            ['value' => static::base64UrlEncode("\x04".$details['ec']['x'].$details['ec']['y'])]
        );
    }

    /**
     * ارسال یک پیام به یک اشتراک پوش.
     *
     * @param  array{endpoint: string, p256dh: string, auth: string}  $subscription
     * @param  array{title?: string, body?: string, url?: string}  $payload
     */
    public static function send(array $subscription, array $payload): Response
    {
        $endpoint = $subscription['endpoint'];

        $body = static::encryptPayload($endpoint, $subscription, json_encode($payload, JSON_UNESCAPED_UNICODE));

        return Http::withHeaders([
            'Authorization' => 'vapid '.static::vapidHeader($endpoint),
            'Content-Type' => 'application/octet-stream',
            'Content-Encoding' => 'aes128gcm',
            'TTL' => '43200',
        ])->withBody($body, 'application/octet-stream')->post($endpoint);
    }

    /**
     * هدر Authorization: توکن VAPID (ES256) + کلید عمومی.
     *
     * @return string مانند «t=JWT,k=PUBLIC»
     */
    public static function vapidHeader(string $endpoint): string
    {
        static::ensureKeys();

        $parts = parse_url($endpoint);
        $audience = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '');

        $header = static::base64UrlEncode(json_encode(['typ' => 'JWT', 'alg' => 'ES256']));

        $claims = static::base64UrlEncode(json_encode([
            'aud' => $audience,
            'exp' => time() + 12 * 3600,
            'sub' => 'mailto:'.(Setting::get('site_email') ?: 'admin@'.($parts['host'] ?? 'localhost')),
        ]));

        $privateKey = openssl_pkey_get_private((string) Setting::get('vapid_private'));

        if (! $privateKey) {
            throw new RuntimeException('کلید خصوصی VAPID خوانده نشد.');
        }

        openssl_sign($header.'.'.$claims, $der, $privateKey, OPENSSL_ALGO_SHA256);

        return 't='.$header.'.'.$claims.'.'.static::base64UrlEncode(static::derToRaw($der))
            .',k='.(string) Setting::get('vapid_public');
    }

    /**
     * رمزنگاری بدنه مطابق RFC 8291 با encoding aes128gcm.
     */
    public static function encryptPayload(string $endpoint, array $subscription, string $plaintext): string
    {
        $clientPublicKey = static::base64UrlDecode($subscription['p256dh']);
        $authSecret = static::base64UrlDecode($subscription['auth']);

        if (strlen($clientPublicKey) !== 65 || strlen($authSecret) !== 16) {
            throw new RuntimeException('کلیدهای اشتراک پوش معتبر نیستند.');
        }

        // کلید ephemeral سمت سرور
        $serverKey = openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);

        $details = openssl_pkey_get_details($serverKey);
        $serverPublicKey = "\x04".$details['ec']['x'].$details['ec']['y'];

        $sharedSecret = openssl_pkey_derive(
            static::publicKeyFromPoint($clientPublicKey),
            $serverKey,
            32
        );

        // IKM = HKDF(auth_secret, ecdh_secret, "WebPush: info\0" || ua_public || as_public)
        $ikm = hash_hkdf(
            'sha256',
            $sharedSecret,
            32,
            "WebPush: info\x00".$clientPublicKey.$serverPublicKey,
            $authSecret
        );

        $salt = random_bytes(16);

        $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\x00", $salt);
        $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\x00", $salt);

        // رکورد آخر با \x02 جدا می‌شود (delimiter در aes128gcm)
        $tag = '';
        $ciphertext = openssl_encrypt(
            $plaintext."\x02",
            'aes-128-gcm',
            $cek,
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            16
        );

        return $salt
            .pack('N', 4096)
            .chr(65)
            .$serverPublicKey
            .$ciphertext.$tag;
    }

    /**
     * کلید عمومی openssl از نقطهٔ فشرده‌نشدهٔ P-256.
     */
    private static function publicKeyFromPoint(string $point): \OpenSSLAsymmetricKey
    {
        $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200').$point;

        $pem = "-----BEGIN PUBLIC KEY-----\n".chunk_split(base64_encode($der), 64, "\n")."-----END PUBLIC KEY-----\n";

        $key = openssl_pkey_get_public($pem);

        if (! $key) {
            throw new RuntimeException('کلید عمومی اشتراک پوش ساخته نشد.');
        }

        return $key;
    }

    /**
     * تبدیل امضای DER به فرمت خام r||s که JWT ES256 می‌خواهد.
     */
    private static function derToRaw(string $der): string
    {
        $offset = 0;

        if (ord($der[$offset++]) !== 0x30) {
            throw new RuntimeException('امضای DER معتبر نیست.');
        }

        $length = ord($der[$offset++]);

        if ($length & 0x80) {
            $bytes = $length & 0x7F;
            $length = 0;

            for ($i = 0; $i < $bytes; $i++) {
                $length = ($length << 8) | ord($der[$offset++]);
            }
        }

        $readInteger = function () use ($der, &$offset): string {
            if (ord($der[$offset++]) !== 0x02) {
                throw new RuntimeException('امضای DER معتبر نیست.');
            }

            $length = ord($der[$offset++]);
            $value = substr($der, $offset, $length);
            $offset += $length;

            return ltrim($value, "\x00");
        };

        $r = $readInteger();
        $s = $readInteger();

        return str_pad($r, 32, "\x00", STR_PAD_LEFT).str_pad($s, 32, "\x00", STR_PAD_LEFT);
    }

    public static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    public static function base64UrlDecode(string $data): string
    {
        return (string) base64_decode(strtr($data, '-_', '+/').str_repeat('=', (4 - strlen($data) % 4) % 4), true);
    }

    /**
     * شناسه کوتاه و یکتا برای لاگ‌ها.
     */
    public static function fingerprint(string $endpoint): string
    {
        return Str::substr(md5($endpoint), 0, 8);
    }
}
