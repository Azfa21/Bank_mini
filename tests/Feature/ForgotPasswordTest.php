<?php

namespace Tests\Feature;

use App\Mail\ResetOtpMail;
use App\Models\Member;
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
            'password_hash' => Hash::make('oldpassword123'),
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

    private function requestOtpCode(string $accountType, string $identifier): string
    {
        Mail::fake();

        $this->post('/forgot-password/request-otp', [
            'account_type' => $accountType,
            'identifier' => $identifier,
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
        $response->assertSee('Lupa password');
        $response->assertSee('Pegawai');
        $response->assertSee('Nasabah');
    }

    public function test_recovery_page_asks_for_login_identifier_not_email(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertSee('Username Akun');
        $response->assertSee('Nomor Siswa');
        $response->assertDontSee('Email Terdaftar');
    }

    public function test_staff_request_otp_sends_email(): void
    {
        Mail::fake();

        $response = $this->post('/forgot-password/request-otp', [
            'account_type' => 'staff',
            'identifier' => 'teller1',
        ]);

        $response->assertSessionHas('otp_sent', true);
        $response->assertSessionHas('success');

        Mail::assertSent(ResetOtpMail::class, function (ResetOtpMail $mail): bool {
            return $mail->hasTo('budi.santoso@example.test');
        });

        $this->assertDatabaseHas('password_reset_otps', [
            'account_type' => 'staff',
            'account_id' => $this->teller->id,
            'email' => 'budi.santoso@example.test',
        ]);

        $this->assertDatabaseHas('audit_logs', ['action' => 'password_reset_otp_requested']);
    }

    public function test_staff_email_alone_does_not_reach_the_account(): void
    {
        Mail::fake();

        // Email ada di tangan orang lain, tapi bukan identifier login-nya.
        $this->post('/forgot-password/request-otp', [
            'account_type' => 'staff',
            'identifier' => 'budi.santoso@example.test',
        ])->assertSessionHasErrors('identifier');

        Mail::assertNothingSent();
        $this->assertDatabaseMissing('password_reset_otps', ['account_type' => 'staff']);
    }

    public function test_nasabah_number_is_enough_to_start_recovery(): void
    {
        Mail::fake();

        $this->post('/forgot-password/request-otp', [
            'account_type' => 'nasabah',
            'identifier' => '2023001',
        ])->assertSessionHas('otp_sent', true);

        Mail::assertSent(ResetOtpMail::class, fn (ResetOtpMail $mail): bool => $mail->hasTo('ani.pertiwi@example.test'));

        $this->assertDatabaseHas('password_reset_otps', [
            'account_type' => 'nasabah',
            'account_id' => $this->nasabah->id,
        ]);
    }

    public function test_staff_can_reset_password_with_valid_otp(): void
    {
        $code = $this->requestOtpCode('staff', 'teller1');

        $response = $this->post('/forgot-password/reset', [
            'account_type' => 'staff',
            'identifier' => 'teller1',
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
        $this->requestOtpCode('staff', 'teller1');

        $response = $this->post('/forgot-password/reset', [
            'account_type' => 'staff',
            'identifier' => 'teller1',
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
        $code = $this->requestOtpCode('staff', 'teller1');

        PasswordResetOtp::query()->update(['expires_at' => now()->subMinute()]);

        $response = $this->post('/forgot-password/reset', [
            'account_type' => 'staff',
            'identifier' => 'teller1',
            'otp' => $code,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('otp');
        $this->assertTrue(Hash::check('password123', $this->teller->fresh()->password_hash));
    }

    public function test_staff_reset_locks_after_max_otp_attempts(): void
    {
        $code = $this->requestOtpCode('staff', 'teller1');

        foreach (range(1, 5) as $attempt) {
            $this->post('/forgot-password/reset', [
                'account_type' => 'staff',
                'identifier' => 'teller1',
                'otp' => '000000',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])->assertSessionHasErrors('otp');
        }

        $this->post('/forgot-password/reset', [
            'account_type' => 'staff',
            'identifier' => 'teller1',
            'otp' => $code,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertSessionHasErrors('otp');

        $this->assertTrue(Hash::check('password123', $this->teller->fresh()->password_hash));
    }

    public function test_staff_reset_validates_password_rules(): void
    {
        $code = $this->requestOtpCode('staff', 'teller1');

        $responseShort = $this->post('/forgot-password/reset', [
            'account_type' => 'staff',
            'identifier' => 'teller1',
            'otp' => $code,
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);
        $responseShort->assertSessionHasErrors('password');

        $responseMismatch = $this->post('/forgot-password/reset', [
            'account_type' => 'staff',
            'identifier' => 'teller1',
            'otp' => $code,
            'password' => 'newpassword123',
            'password_confirmation' => 'different123',
        ]);
        $responseMismatch->assertSessionHasErrors('password');
    }

    public function test_nasabah_request_otp_sends_email(): void
    {
        Mail::fake();

        $this->post('/forgot-password/request-otp', [
            'account_type' => 'nasabah',
            'identifier' => '2023001',
        ])->assertSessionHas('otp_sent', true);

        Mail::assertSent(ResetOtpMail::class, fn (ResetOtpMail $mail): bool => $mail->hasTo('ani.pertiwi@example.test'));

        $this->assertDatabaseHas('password_reset_otps', [
            'account_type' => 'nasabah',
            'account_id' => $this->nasabah->id,
        ]);
    }

    public function test_nasabah_can_reset_password_with_valid_otp(): void
    {
        $code = $this->requestOtpCode('nasabah', '2023001');
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
            'identifier' => '2023001',
            'otp' => $code,
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $fresh = $this->nasabah->fresh();

        $this->assertTrue(Hash::check('password-baru-123', $fresh->password_hash));
        $this->assertDatabaseMissing('mobile_tokens', ['saver_id' => $this->nasabah->id]);

        foreach ([$firstMobileToken, $secondMobileToken] as $mobileToken) {
            $this->withToken($mobileToken)
                ->getJson(route('api.mobile.me'))
                ->assertUnauthorized();
        }

        // PIN transaksi tidak dilalui alur OTP ini.
        $this->assertTrue(Hash::check('123456', $fresh->pin_hash));

        $loginResponse = $this->post('/login', [
            'identifier' => '2023001',
            'secret' => 'password-baru-123',
        ]);
        $loginResponse->assertRedirect(route('nasabah.dashboard'));
        $this->assertAuthenticated('nasabah');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'password_reset_success',
            'entity_name' => 'saver',
            'entity_id' => $this->nasabah->id,
        ]);
    }

    public function test_passwordless_nasabah_can_create_the_first_password_with_otp(): void
    {
        $saver = Saver::create([
            'student_number' => '2023005',
            'full_name' => 'Nasabah Tanpa Password',
            'pin_hash' => Hash::make('123456'),
            'email' => 'tanpa.password@example.test',
            'qr_code_value' => 'qr-test-2023005',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        $code = $this->requestOtpCode('nasabah', '2023005');

        $this->post('/forgot-password/reset', [
            'account_type' => 'nasabah',
            'identifier' => '2023005',
            'otp' => $code,
            'password' => 'password-pertama-123',
            'password_confirmation' => 'password-pertama-123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('password-pertama-123', $saver->fresh()->password_hash));

        $this->post('/login', [
            'identifier' => '2023005',
            'secret' => 'password-pertama-123',
        ])->assertRedirect(route('nasabah.dashboard'));
    }

    public function test_nasabah_reset_validates_password_rules(): void
    {
        $code = $this->requestOtpCode('nasabah', '2023001');

        $responseShort = $this->post('/forgot-password/reset', [
            'account_type' => 'nasabah',
            'identifier' => '2023001',
            'otp' => $code,
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);
        $responseShort->assertSessionHasErrors('password');

        $responseMismatch = $this->post('/forgot-password/reset', [
            'account_type' => 'nasabah',
            'identifier' => '2023001',
            'otp' => $code,
            'password' => 'password-baru-123',
            'password_confirmation' => 'nilai-lain-456',
        ]);
        $responseMismatch->assertSessionHasErrors('password');
    }

    public function test_api_staff_otp_and_reset_endpoints_return_json(): void
    {
        Mail::fake();

        $this->postJson('/api/forgot-password/request-otp', [
            'account_type' => 'staff',
            'identifier' => 'teller1',
        ])->assertOk()->assertJson(['success' => true]);

        $code = null;
        Mail::assertSent(ResetOtpMail::class, function (ResetOtpMail $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        $this->postJson('/api/forgot-password/reset', [
            'account_type' => 'staff',
            'identifier' => 'teller1',
            'otp' => $code,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertOk()->assertJson(['success' => true]);

        $errorResponse = $this->postJson('/api/forgot-password/reset', [
            'account_type' => 'staff',
            'identifier' => 'tidak-ada',
            'otp' => '000000',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $errorResponse->assertStatus(422);
        $errorResponse->assertJson(['success' => false]);
    }

    public function test_mobile_forgot_password_endpoints_work(): void
    {
        Mail::fake();

        $this->postJson('/api/mobile/forgot-password/request-otp', [
            'account_type' => 'nasabah',
            'identifier' => '2023001',
        ])->assertOk()->assertJson(['success' => true]);

        $code = null;
        Mail::assertSent(ResetOtpMail::class, function (ResetOtpMail $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        $this->postJson('/api/mobile/forgot-password/reset', [
            'account_type' => 'nasabah',
            'identifier' => '2023001',
            'otp' => $code,
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertTrue(Hash::check('password-baru-123', $this->nasabah->fresh()->password_hash));
        $this->assertTrue(Hash::check('123456', $this->nasabah->fresh()->pin_hash));
    }

    /**
     * Akun yang tidak ada dijawab jujur: username diberi tahu belum
     * terdaftar dan tidak ada email yang dikirim, bukan notifikasi palsu
     * seolah kode sudah terkirim.
     */
    public function test_otp_request_reports_an_unknown_username(): void
    {
        Mail::fake();

        $response = $this->post('/forgot-password/request-otp', [
            'account_type' => 'staff',
            'identifier' => 'teller-hantu',
        ]);

        Mail::assertNothingSent();
        $response->assertSessionHasErrors('identifier');
        $response->assertSessionMissing('otp_sent');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'password_reset_otp_request_ignored',
            'entity_id' => null,
        ]);
    }

    public function test_otp_request_is_rejected_for_a_blocked_account(): void
    {
        Mail::fake();

        $response = $this->post('/forgot-password/request-otp', [
            'account_type' => 'staff',
            'identifier' => 'teller_blokir',
        ]);

        Mail::assertNothingSent();
        $response->assertSessionHasErrors('identifier');
        $response->assertSessionMissing('otp_sent');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'password_reset_otp_request_ignored',
            'entity_id' => $this->blockedUser->id,
        ]);
    }

    public function test_otp_request_is_rejected_for_a_blocked_nasabah(): void
    {
        Mail::fake();

        $response = $this->post('/forgot-password/request-otp', [
            'account_type' => 'nasabah',
            'identifier' => '2023002',
        ]);

        Mail::assertNothingSent();
        $response->assertSessionHasErrors('identifier');
        $response->assertSessionMissing('otp_sent');
        $this->assertTrue(Hash::check('123456', $this->blockedNasabah->fresh()->pin_hash));
    }

    /**
     * Anggota yang belum diaktivasi belum punya baris `savers`, jadi belum
     * punya email penerima. Permintaannya ditolak dan tidak ada email yang
     * dikirim, bukan diberi notifikasi "email terkirim" palsu.
     */
    public function test_otp_request_is_rejected_for_a_member_without_a_saver(): void
    {
        Mail::fake();

        Member::create([
            'nis' => '17801',
            'full_name' => 'Anggota Baru',
            'graduation_year' => '2027',
            'status' => Member::STATUS_ACCOUNT_INACTIVE,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->post('/forgot-password/request-otp', [
            'account_type' => 'nasabah',
            'identifier' => '17801',
        ]);

        Mail::assertNothingSent();
        $response->assertSessionHasErrors('identifier');
        $response->assertSessionMissing('otp_sent');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'password_reset_otp_request_ignored',
            'entity_id' => null,
        ]);
    }

    public function test_otp_request_is_rejected_when_the_penabung_has_no_email(): void
    {
        Mail::fake();

        $saver = Saver::create([
            'student_number' => '2023099',
            'full_name' => 'Penabung Tanpa Email',
            'pin_hash' => Hash::make('123456'),
            'qr_code_value' => 'qr-test-2023099',
            'created_by' => $this->admin->id,
            'status' => 'active',
            'email' => null,
        ]);

        $response = $this->post('/forgot-password/request-otp', [
            'account_type' => 'nasabah',
            'identifier' => '2023099',
        ]);

        Mail::assertNothingSent();
        $response->assertSessionHasErrors('identifier');
        $response->assertSessionMissing('otp_sent');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'password_reset_otp_request_ignored',
            'entity_id' => $saver->id,
        ]);
    }

    public function test_otp_resend_within_cooldown_keeps_step_two_and_sends_one_email(): void
    {
        Mail::fake();

        $this->from(route('password.request'))->post('/forgot-password/request-otp', [
            'account_type' => 'nasabah',
            'identifier' => '2023001',
        ])->assertSessionHas('otp_sent', true);

        $second = $this->from(route('password.request'))->post('/forgot-password/request-otp', [
            'account_type' => 'nasabah',
            'identifier' => '2023001',
        ]);

        $second->assertSessionHasNoErrors();
        $second->assertSessionHas('otp_sent', true);
        $second->assertSessionHas('email', 'ani.pertiwi@example.test');

        Mail::assertSent(ResetOtpMail::class, 1);
        $this->assertDatabaseHas('audit_logs', ['action' => 'password_reset_otp_request_throttled']);
    }

    public function test_step_two_shows_the_registered_email(): void
    {
        Mail::fake();

        $response = $this->from(route('password.request'))->post('/forgot-password/request-otp', [
            'account_type' => 'staff',
            'identifier' => 'teller1',
        ]);

        $response->assertSessionHas('email', 'budi.santoso@example.test');

        $followUp = $this->get('/forgot-password');
        $followUp->assertSee('budi.santoso@example.test', false);
    }

    public function test_unknown_identifier_never_reaches_step_two(): void
    {
        Mail::fake();

        $response = $this->from(route('password.request'))->post('/forgot-password/request-otp', [
            'account_type' => 'staff',
            'identifier' => 'teller-hantu',
        ]);

        $response->assertSessionMissing('email');
        $response->assertSessionMissing('otp_sent');

        $followUp = $this->get('/forgot-password');
        $followUp->assertDontSee('Kode verifikasi dikirim ke', false);
    }

    public function test_full_email_reaches_the_browser_on_step_two(): void
    {
        Mail::fake();

        $this->from(route('password.request'))->post('/forgot-password/request-otp', [
            'account_type' => 'nasabah',
            'identifier' => '2023001',
        ]);

        $followUp = $this->get('/forgot-password');
        $followUp->assertSee('ani.pertiwi@example.test', false);
    }

    public function test_email_survives_a_failed_otp_attempt(): void
    {
        $this->requestOtpCode('staff', 'teller1');

        $response = $this->from(route('password.request'))->post('/forgot-password/reset', [
            'account_type' => 'staff',
            'identifier' => 'teller1',
            'otp' => '000000',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('otp');
        $response->assertSessionHas('email', 'budi.santoso@example.test');
    }

    public function test_api_request_otp_returns_the_registered_email(): void
    {
        Mail::fake();

        $this->postJson('/api/mobile/forgot-password/request-otp', [
            'account_type' => 'nasabah',
            'identifier' => '2023001',
        ])->assertOk()->assertJson([
            'success' => true,
            'email' => 'ani.pertiwi@example.test',
        ]);

        $this->postJson('/api/mobile/forgot-password/request-otp', [
            'account_type' => 'nasabah',
            'identifier' => '9999999',
        ])->assertStatus(404)->assertJson([
            'success' => false,
            'email' => null,
        ]);
    }
}
