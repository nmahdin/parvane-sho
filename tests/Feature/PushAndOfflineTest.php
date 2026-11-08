<?php

namespace Tests\Feature;

use App\Models\Challenge;
use App\Models\Participant;
use App\Models\PushSubscription;
use App\Models\Setting;
use App\Support\WebPush;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * یادآور پوش نوتیفیکیشن، سرویس‌ورکر آفلاین، ادامهٔ پخش صوت و حالت تاریک.
 */
class PushAndOfflineTest extends TestCase
{
    use RefreshDatabase;

    private function makeChallenge(array $attributes = []): Challenge
    {
        return Challenge::create(array_merge([
            'title' => 'چالش یادآور',
            'slug' => 'reminder-challenge',
            'is_published' => true,
            'daily_release' => true,
            'starts_on' => Carbon::today()->subDay(),
            'ends_on' => Carbon::today()->addDays(5),
        ], $attributes));
    }

    /**
     * کلید عمومی P-256 واقعی (۶۵ بایت) برای p256dh — بایت‌های تصادفی
     * نقطهٔ معتبر منحنی نیستند و رمزنگاری RFC 8291 را می‌شکنند.
     */
    private function clientKeyPair(): array
    {
        $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $details = openssl_pkey_get_details($key);

        return [
            'p256dh' => WebPush::base64UrlEncode("\x04".$details['ec']['x'].$details['ec']['y']),
            'auth' => WebPush::base64UrlEncode(random_bytes(16)),
        ];
    }

    private function makeParticipant(Challenge $challenge, string $name = 'زهرا'): Participant
    {
        return Participant::create(['challenge_id' => $challenge->id, 'name' => $name]);
    }

    public function test_participant_can_subscribe_to_push_reminders(): void
    {
        $challenge = $this->makeChallenge();
        $participant = $this->makeParticipant($challenge);

        $this->postJson(route('participant.push', $participant), [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'keys' => [
                'p256dh' => WebPush::base64UrlEncode(random_bytes(65)),
                'auth' => WebPush::base64UrlEncode(random_bytes(16)),
            ],
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertSame(
            'https://fcm.googleapis.com/fcm/send/abc123',
            PushSubscription::query()->firstOrFail()->endpoint
        );
    }

    public function test_subscription_can_be_cancelled(): void
    {
        $challenge = $this->makeChallenge();
        $participant = $this->makeParticipant($challenge);

        $participant->pushSubscription()->create($this->clientKeyPair() + [
            'endpoint' => 'https://updates.push.services.mozilla.com/x1',
        ]);

        $this->post(route('participant.push.cancel', $participant))->assertRedirect();

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_reminder_command_posts_encrypted_push_to_endpoints(): void
    {
        $challenge = $this->makeChallenge();
        $participant = $this->makeParticipant($challenge);

        $participant->pushSubscription()->create($this->clientKeyPair() + [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/xyz789',
        ]);

        Http::fake(['*' => Http::response('', 201)]);

        $this->artisan('reminders:send')->assertSuccessful();

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'fcm.googleapis.com')
                && str_starts_with($request->header('Authorization')[0] ?? '', 'vapid t=')
                && $request->header('Content-Encoding')[0] === 'aes128gcm';
        });
    }

    public function test_dead_subscriptions_are_removed_on_gone_response(): void
    {
        $challenge = $this->makeChallenge();
        $participant = $this->makeParticipant($challenge);

        $participant->pushSubscription()->create($this->clientKeyPair() + [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/dead1',
        ]);

        Http::fake(['*' => Http::response('', 410)]);

        $this->artisan('reminders:send')->assertSuccessful();

        $this->assertDatabaseCount('push_subscriptions', 0);
    }

    public function test_vapid_keys_are_generated_once_and_reused(): void
    {
        $first = WebPush::publicKey();

        $this->assertNotEmpty($first);
        $this->assertSame($first, WebPush::publicKey());
        $this->assertNotEmpty(Setting::get('vapid_private'));
    }

    public function test_payload_is_encrypted_with_aes128gcm_framing(): void
    {
        $clientKey = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $details = openssl_pkey_get_details($clientKey);
        $point = "\x04".$details['ec']['x'].$details['ec']['y'];

        $body = WebPush::encryptPayload('https://example.com/push', [
            'endpoint' => 'https://example.com/push',
            'p256dh' => WebPush::base64UrlEncode($point),
            'auth' => WebPush::base64UrlEncode(random_bytes(16)),
        ], 'سلام');

        // ساختار aes128gcm: salt(16) + rs(4) + idlen(1=65) + key(65) + ciphertext
        $this->assertSame(16, strlen(substr($body, 0, 16)));
        $this->assertSame(4096, unpack('N', substr($body, 16, 4))[1]);
        $this->assertSame(65, ord($body[20]));
        $this->assertSame("\x04", $body[21]); // نقطهٔ فشرده‌نشدهٔ P-256
        $this->assertGreaterThan(86, strlen($body));
    }

    public function test_service_worker_file_is_served_and_caches_day_files(): void
    {
        $path = public_path('sw.js');

        $this->assertFileExists($path);

        $sw = file_get_contents($path);

        $this->assertStringContainsString("'/day-files/'", $sw);
        $this->assertStringContainsString('showNotification', $sw);
        $this->assertStringContainsString('notificationclick', $sw);
    }

    public function test_audio_players_carry_an_id_for_resume(): void
    {
        $challenge = $this->makeChallenge();
        $participant = $this->makeParticipant($challenge);

        \Illuminate\Support\Facades\Storage::disk('local')->put('day-files/note.wav', 'RIFF....WAVE');
        $challenge->dayFiles()->create([
            'day_number' => 1,
            'title' => 'صوت روز اول',
            'path' => 'day-files/note.wav',
            'original_name' => 'note.wav',
            'mime' => 'audio/wav',
            'is_audio' => true,
            'sort_order' => 1,
        ]);

        $html = $this->get(route('participant.show', $participant))->getContent();

        $this->assertStringContainsString('data-audio-id="1"', $html);
        $this->assertStringContainsString('فعال‌سازی یادآور روزانه', $html);
    }

    public function test_dark_mode_toggle_ships_in_headers_and_built_assets(): void
    {
        $challenge = $this->makeChallenge();

        $this->assertStringContainsString('data-theme-toggle', $this->get(route('challenges.show', $challenge))->getContent());

        $this->loginAsAdmin();
        $this->assertStringContainsString('data-theme-toggle', $this->get(route('panel.dashboard'))->getContent());

        $css = file_get_contents(public_path('build/assets/'.collect(glob(public_path('build/assets/*.css')))->map(fn ($f) => basename($f))->first()));
        $this->assertStringContainsString('html.dark', $css);

        $js = file_get_contents(collect(glob(public_path('build/assets/app-*.js')))->first());
        $this->assertStringContainsString('pv-theme', $js);
        $this->assertStringContainsString('pv-audio-', $js);
        $this->assertStringContainsString('serviceWorker', $js);
    }

    public function test_day_images_fade_from_blur_to_clear_on_load(): void
    {
        $challenge = $this->makeChallenge();

        \Illuminate\Support\Facades\Storage::disk('local')->put('day-files/pic.jpg', 'fakejpg');
        $challenge->dayFiles()->create([
            'day_number' => 1,
            'title' => 'تصویر روز',
            'path' => 'day-files/pic.jpg',
            'original_name' => 'pic.jpg',
            'mime' => 'image/jpeg',
            'is_audio' => false,
            'sort_order' => 1,
        ]);

        $html = $this->get(route('challenges.show', $challenge))->getContent();

        // عکس روز کلاس انیمیشن دارد و تا لود، مات است
        $this->assertStringContainsString('class="day-image img-reveal"', $html);

        $css = file_get_contents(collect(glob(public_path('build/assets/*.css')))->first());
        $this->assertStringContainsString('img-reveal', $css);
        $this->assertStringContainsString('is-loaded', $css);

        $js = file_get_contents(collect(glob(public_path('build/assets/app-*.js')))->first());
        $this->assertStringContainsString('img-reveal', $js);
        $this->assertStringContainsString('is-loaded', $js);
    }
}
