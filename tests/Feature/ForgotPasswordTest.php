<?php

namespace Tests\Feature;

use App\Mail\ResetOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\Saver;
use App\Models\User;
use App\Services\MobileTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teller;

    protected User $blockedUser;

    protected Saver $nasabah;

    protected Saver $blockedNasabah;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ThrottleRequests::class);

        $this->admin = User::create([
            'username' => 'admin',
            'email' => 'admin@example.test',
            'password_hash' => Hash::make('password123'),
            'full_name' => 'Administrator Bank',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->teller = User::create([
            'username' => 'teller1',
            'email' => 'budi.santoso@example.test',
            'password_hash' => Hash::make('password123'),
            'full_name' => 'Budi Santoso',
            'role' => 'teller',
            'status' => 'active',
        ]);

        $this->blockedUser = User::create([
            'username' => 'teller_blokir',
            'email' => 'agus.blokir@example.test',
            'password_hash' => Hash::make('password123'),
            'full_name' => 'Agus Blokir',
            'role' => 'teller',
            'status' => 'blocked',
        ]);

        $this->nasabah = Saver::create([
            'student_number' => '2023001',
            'full_name' => 'Ani Pertiwi',
            'class_name' => 'X RPL 1',
            'phone_number' => '081234567890',
            'email' => 'ani.pertiwi@example.test',
            'pin_hash' => Hash::make('123456'),
            'qr_code_value' => 'qr-test-2023001',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        $this->blockedNasabah = Saver::create([
            'student_number' => '2023002',
            'full_name' => 'Joko Blokir',
            'class_name' => 'X RPL 2',
            'phone_number' => '081234567891',
            'email' => 'joko.blokir@example.test',
            'pin_hash' => Hash::make('123456'),
            'qr_code_value' => 'qr-test-2023002',
            'created_by' => $this->admin->id,
            'status' => 'blocked',
        ]);
    }

    private function requestOtpCode(string $accountType, string $email): string
    {
        Mail::fake();

        $this->post('/forgot-password/request-otp', [
            'account_type' => $accountType,
            'email' => $email,
        ])->assertSessionHasNoErrors();

        $code = null;

        Mail::assertSent(ResetOtpMail::class, function (ResetOtpMail $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        $this->assertNotNull($code, 'Kode OTP seharusnya dikirim ke email.');

        return (string) $code;
    }

    public function test_forgot_password_page_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertOk();
        $response->assertSee('Pemulihan Kredensial');
        $response->assertSee('Pegawai');
        $response->assertSee('Nasabah');
    }

    public function test_staff_request_otp_sends_email(): void
    {
        Mail::fake();

        $response = $this->post('/forgot-password/request-otp', [
            'account_type' => 'staff',
            'email' => 'budi.santoso@example.test',
        ]);

        $response->assertSessionHas('otp_sent', true);
        $response->assertSessionHas('success');

        Mail::assertSent(ResetOtpMail::class, function (ResetOtpMail $mail): bool {
            return $mail->hasTo('budi.santoso@example.test') && $mail->purpose === 'password';
        });

        $this->assertDatabaseHas('password_reset_otps', [
            'account_type' => 'staff',
            'email' => 'budi.santoso@example.test',
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'password_reset_otp_requested']);
    }

    public function test_staff_request_otp_fails_when_email_not_registered(): void
    {
        Mail::fake();

        $response = $this->post('/forgot-password/request-otp', [
            'account_type' => 'staff',
            'email' => 'tidak.ada@example.test',
        ]);

        $response->assertSessionHasErrors('email');
        Mail::assertNothingSent();

        $this->assertDatabaseHas('audit_logs', ['action' => 'password_reset_otp_request_failed']);
    }

    public function test_staff_request_otp_fails_when_account_is_blocked(): void
    {
        Mail::fake();

        $response = $this->post('/forgot-password/request-otp', [
            'account_type' => 'staff',
            'email' => 'agus.blokir@example.test',
        ]);

        $response->assertSessionHasErrors('email');
        Mail::assertNothingSent();

        $this->assertDatabaseHas('audit_logs', ['action' => 'password_reset_otp_request_blocked']);
    }

    public function test_staff_can_reset_password_with_valid_otp(): void
    {
        $code = $this->requestOtpCode('staff', 'budi.santoso@example.test');

        $response = $this->post('/forgot-password/reset', [
            'account_type' => 'staff',
            'email' => 'budi.santoso@example.test',
            'otp' => $code,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('newpassword123', $this->teller->fresh()->password_hash));

        $loginResponse = $this->post('/login', [
            'identifier' => 'teller1',
            'secret' => 'newpassword123',
        ]);
        $loginResponse->assertRedirect(route('teller.dashboard'));
        $this->assertAuthenticated('web');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'password_reset_success',
            'entity_name' => 'user',
            'entity_id' => $this->teller->id,
        ]);
    }

    public function test_staff_reset_fails_with_wrong_otp(): void
    {
        $this->requestOtpCode('staff', 'budi.santoso@example.test');

        $response = $this->post('/forgot-password/reset', [
            'account_type' => 'staff',
            'email' => 'budi.santoso@example.test',
            'otp' => '000000',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('otp');
        $this->assertTrue(Hash::check('password123', $this->teller->fresh()->password_hash));

        $this->assertDatabaseHas('audit_logs', ['action' => 'password_reset_otp_failed']);
    }

    public function test_staff_reset_fails_when_otp_expired(): void
    {
        $code = $this->requestOtpCode('staff', 'budi.santoso@example.test');

        PasswordResetOtp::query()->update(['expires_at' => now()->subMinute()]);

        $response = $this->post('/forgot-password/reset', [
            'account_type' => 'staff',
            'email' => 'budi.santoso@example.test',
            'otp' => $code,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('otp');
        $this->assertTrue(Hash::check('password123', $this->teller->fresh()->password_hash));
    }

    public function test_staff_reset_locks_after_max_otp_attempts(): void
    {
        $code = $this->requestOtpCode('staff', 'budi.santoso@example.test');

        foreach (range(1, 5) as $attempt) {
            $this->post('/forgot-password/reset', [
                'account_type' => 'staff',
                'email' => 'budi.santoso@example.test',
                'otp' => '000000',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])->assertSessionHasErrors('otp');
        }

        $this->post('/forgot-password/reset', [
            'account_type' => 'staff',
            'email' => 'budi.santoso@example.test',
            'otp' => $code,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertSessionHasErrors('otp');

        $this->assertTrue(Hash::check('password123', $this->teller->fresh()->password_hash));
    }

    public function test_staff_reset_validates_password_rules(): void
    {
        $code = $this->requestOtpCode('staff', 'budi.santoso@example.test');

        $responseShort = $this->post('/forgot-password/reset', [
            'account_type' => 'staff',
            'email' => 'budi.santoso@example.test',
            'otp' => $code,
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);
        $responseShort->assertSessionHasErrors('password');

        $responseMismatch = $this->post('/forgot-password/reset', [
            'account_type' => 'staff',
            'email' => 'budi.santoso@example.test',
            'otp' => $code,
            'password' => 'newpassword123',
            'password_confirmation' => 'different123',
        ]);
        $responseMismatch->assertSessionHasErrors('password');
    }

    public function test_nasabah_request_otp_sends_pin_email(): void
    {
        Mail::fake();

        $this->post('/forgot-password/request-otp', [
            'account_type' => 'nasabah',
            'email' => 'ani.pertiwi@example.test',
        ])->assertSessionHas('otp_sent', true);

        Mail::assertSent(ResetOtpMail::class, function (ResetOtpMail $mail): bool {
            return $mail->hasTo('ani.pertiwi@example.test') && $mail->purpose === 'pin';
        });
    }

    public function test_nasabah_can_reset_pin_with_valid_otp(): void
    {
        $code = $this->requestOtpCode('nasabah', 'ani.pertiwi@example.test');
        $tokens = app(MobileTokenService::class);
        $firstMobileToken = $tokens->issue($this->nasabah, 'existing-device')['plain'];
        $secondMobileToken = $tokens->issue($this->nasabah, 'other-device')['plain'];

        foreach ([$firstMobileToken, $secondMobileToken] as $mobileToken) {
            $this->withToken($mobileToken)
                ->getJson(route('api.mobile.me'))
                ->assertOk();
        }

        $response = $this->post('/forgot-password/reset', [
            'account_type' => 'nasabah',
            'email' => 'ani.pertiwi@example.test',
            'otp' => $code,
            'pin' => '654321',
            'pin_confirmation' => '654321',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('654321', $this->nasabah->fresh()->pin_hash));
        $this->assertDatabaseMissing('mobile_tokens', ['saver_id' => $this->nasabah->id]);

        foreach ([$firstMobileToken, $secondMobileToken] as $mobileToken) {
            $this->withToken($mobileToken)
                ->getJson(route('api.mobile.me'))
                ->assertUnauthorized();
        }

        $loginResponse = $this->post('/login', [
            'identifier' => '2023001',
            'secret' => '654321',
        ]);
        $loginResponse->assertRedirect(route('nasabah.dashboard'));
        $this->assertAuthenticated('nasabah');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'pin_reset_success',
            'entity_name' => 'saver',
            'entity_id' => $this->nasabah->id,
        ]);
    }

    public function test_nasabah_reset_fails_when_account_is_blocked(): void
    {
        $response = $this->post('/forgot-password/request-otp', [
            'account_type' => 'nasabah',
            'email' => 'joko.blokir@example.test',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('123456', $this->blockedNasabah->fresh()->pin_hash));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'pin_reset_otp_request_blocked',
            'entity_name' => 'saver',
            'entity_id' => $this->blockedNasabah->id,
        ]);
    }

    public function test_nasabah_reset_validates_pin_rules(): void
    {
        $code = $this->requestOtpCode('nasabah', 'ani.pertiwi@example.test');

        $responseShort = $this->post('/forgot-password/reset', [
            'account_type' => 'nasabah',
            'email' => 'ani.pertiwi@example.test',
            'otp' => $code,
            'pin' => '1234',
            'pin_confirmation' => '1234',
        ]);
        $responseShort->assertSessionHasErrors('pin');

        $responseNonNumeric = $this->post('/forgot-password/reset', [
            'account_type' => 'nasabah',
            'email' => 'ani.pertiwi@example.test',
            'otp' => $code,
            'pin' => 'abcdef',
            'pin_confirmation' => 'abcdef',
        ]);
        $responseNonNumeric->assertSessionHasErrors('pin');

        $responseMismatch = $this->post('/forgot-password/reset', [
            'account_type' => 'nasabah',
            'email' => 'ani.pertiwi@example.test',
            'otp' => $code,
            'pin' => '654321',
            'pin_confirmation' => '111111',
        ]);
        $responseMismatch->assertSessionHasErrors('pin');
    }

    public function test_api_staff_otp_and_reset_endpoints_return_json(): void
    {
        Mail::fake();

        $this->postJson('/api/forgot-password/request-otp', [
            'account_type' => 'staff',
            'email' => 'budi.santoso@example.test',
        ])->assertOk()->assertJson(['success' => true]);

        $code = null;
        Mail::assertSent(ResetOtpMail::class, function (ResetOtpMail $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        $this->postJson('/api/forgot-password/reset', [
            'account_type' => 'staff',
            'email' => 'budi.santoso@example.test',
            'otp' => $code,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertOk()->assertJson(['success' => true]);

        $errorResponse = $this->postJson('/api/forgot-password/reset', [
            'account_type' => 'staff',
            'email' => 'tidak.ada@example.test',
            'otp' => '000000',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $errorResponse->assertStatus(422);
        $errorResponse->assertJson(['success' => false]);
    }

    public function test_mobile_forgot_pin_endpoints_work(): void
    {
        Mail::fake();

        $this->postJson('/api/mobile/forgot-pin/request-otp', [
            'account_type' => 'nasabah',
            'email' => 'ani.pertiwi@example.test',
        ])->assertOk()->assertJson(['success' => true]);

        $code = null;
        Mail::assertSent(ResetOtpMail::class, function (ResetOtpMail $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        $this->postJson('/api/mobile/forgot-pin/reset', [
            'account_type' => 'nasabah',
            'email' => 'ani.pertiwi@example.test',
            'otp' => $code,
            'pin' => '654321',
            'pin_confirmation' => '654321',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertTrue(Hash::check('654321', $this->nasabah->fresh()->pin_hash));
    }
}
