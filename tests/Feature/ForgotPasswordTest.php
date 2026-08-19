<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teller;

    protected User $blockedUser;

    protected Customer $nasabah;

    protected Customer $blockedNasabah;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'username' => 'admin',
            'password_hash' => Hash::make('password123'),
            'full_name' => 'Administrator Bank',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->teller = User::create([
            'username' => 'teller1',
            'password_hash' => Hash::make('password123'),
            'full_name' => 'Budi Santoso',
            'role' => 'teller',
            'status' => 'active',
        ]);

        $this->blockedUser = User::create([
            'username' => 'teller_blokir',
            'password_hash' => Hash::make('password123'),
            'full_name' => 'Agus Blokir',
            'role' => 'teller',
            'status' => 'blocked',
        ]);

        $this->nasabah = Customer::create([
            'student_number' => '2023001',
            'full_name' => 'Ani Pertiwi',
            'class_name' => 'X RPL 1',
            'phone_number' => '081234567890',
            'pin_hash' => Hash::make('123456'),
            'qr_code_value' => 'qr-test-2023001',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        $this->blockedNasabah = Customer::create([
            'student_number' => '2023002',
            'full_name' => 'Joko Blokir',
            'class_name' => 'X RPL 2',
            'phone_number' => '081234567891',
            'pin_hash' => Hash::make('123456'),
            'qr_code_value' => 'qr-test-2023002',
            'created_by' => $this->admin->id,
            'status' => 'blocked',
        ]);
    }

    public function test_forgot_password_page_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertOk();
        $response->assertSee('Pemulihan Kredensial');
        $response->assertSee('Pegawai / Staff');
        $response->assertSee('Nasabah / Siswa');
    }

    public function test_staff_can_reset_password_with_valid_identity(): void
    {
        $response = $this->post('/forgot-password/staff', [
            'username' => 'teller1',
            'full_name' => 'Budi Santoso',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('newpassword123', $this->teller->fresh()->password_hash));

        // Can login with new password
        $loginResponse = $this->post('/login', [
            'identifier' => 'teller1',
            'secret' => 'newpassword123',
        ]);
        $loginResponse->assertRedirect(route('teller.dashboard'));
        $this->assertAuthenticated('web');

        // Audit log exists
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'password_reset_success',
            'entity_name' => 'user',
            'entity_id' => $this->teller->id,
        ]);
    }

    public function test_staff_reset_fails_when_full_name_does_not_match(): void
    {
        $response = $this->post('/forgot-password/staff', [
            'username' => 'teller1',
            'full_name' => 'Nama Yang Salah',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertTrue(Hash::check('password123', $this->teller->fresh()->password_hash));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'password_reset_failed',
            'entity_name' => 'user',
        ]);
    }

    public function test_staff_reset_fails_when_user_does_not_exist(): void
    {
        $response = $this->post('/forgot-password/staff', [
            'username' => 'nonexistent_user',
            'full_name' => 'Siapa Saja',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('username');
    }

    public function test_staff_reset_fails_when_account_is_blocked(): void
    {
        $response = $this->post('/forgot-password/staff', [
            'username' => 'teller_blokir',
            'full_name' => 'Agus Blokir',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertTrue(Hash::check('password123', $this->blockedUser->fresh()->password_hash));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'password_reset_blocked',
            'entity_name' => 'user',
            'entity_id' => $this->blockedUser->id,
        ]);
    }

    public function test_staff_reset_validates_password_rules(): void
    {
        // Too short (< 8 chars)
        $responseShort = $this->post('/forgot-password/staff', [
            'username' => 'teller1',
            'full_name' => 'Budi Santoso',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);
        $responseShort->assertSessionHasErrors('password');

        // Mismatched confirmation
        $responseMismatch = $this->post('/forgot-password/staff', [
            'username' => 'teller1',
            'full_name' => 'Budi Santoso',
            'password' => 'newpassword123',
            'password_confirmation' => 'different123',
        ]);
        $responseMismatch->assertSessionHasErrors('password');
    }

    public function test_nasabah_can_reset_pin_with_valid_identity(): void
    {
        $response = $this->post('/forgot-password/nasabah', [
            'student_number' => '2023001',
            'class_name' => 'X RPL 1',
            'pin' => '654321',
            'pin_confirmation' => '654321',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('654321', $this->nasabah->fresh()->pin_hash));

        // Can login with new PIN
        $loginResponse = $this->post('/login', [
            'identifier' => '2023001',
            'secret' => '654321',
        ]);
        $loginResponse->assertRedirect(route('nasabah.dashboard'));
        $this->assertAuthenticated('nasabah');

        // Audit log exists
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'pin_reset_success',
            'entity_name' => 'customer',
            'entity_id' => $this->nasabah->id,
        ]);
    }

    public function test_nasabah_reset_fails_when_class_name_does_not_match(): void
    {
        $response = $this->post('/forgot-password/nasabah', [
            'student_number' => '2023001',
            'class_name' => 'XII TKJ 3',
            'pin' => '654321',
            'pin_confirmation' => '654321',
        ]);

        $response->assertSessionHasErrors('student_number');
        $this->assertTrue(Hash::check('123456', $this->nasabah->fresh()->pin_hash));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'pin_reset_failed',
            'entity_name' => 'customer',
        ]);
    }

    public function test_nasabah_reset_fails_when_account_is_blocked(): void
    {
        $response = $this->post('/forgot-password/nasabah', [
            'student_number' => '2023002',
            'class_name' => 'X RPL 2',
            'pin' => '654321',
            'pin_confirmation' => '654321',
        ]);

        $response->assertSessionHasErrors('student_number');
        $this->assertTrue(Hash::check('123456', $this->blockedNasabah->fresh()->pin_hash));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'pin_reset_blocked',
            'entity_name' => 'customer',
            'entity_id' => $this->blockedNasabah->id,
        ]);
    }

    public function test_nasabah_reset_validates_pin_rules(): void
    {
        // Not 6 digits (e.g. 4 digits)
        $responseShort = $this->post('/forgot-password/nasabah', [
            'student_number' => '2023001',
            'class_name' => 'X RPL 1',
            'pin' => '1234',
            'pin_confirmation' => '1234',
        ]);
        $responseShort->assertSessionHasErrors('pin');

        // Non-numeric
        $responseNonNumeric = $this->post('/forgot-password/nasabah', [
            'student_number' => '2023001',
            'class_name' => 'X RPL 1',
            'pin' => 'abcdef',
            'pin_confirmation' => 'abcdef',
        ]);
        $responseNonNumeric->assertSessionHasErrors('pin');

        // Mismatched confirmation
        $responseMismatch = $this->post('/forgot-password/nasabah', [
            'student_number' => '2023001',
            'class_name' => 'X RPL 1',
            'pin' => '654321',
            'pin_confirmation' => '111111',
        ]);
        $responseMismatch->assertSessionHasErrors('pin');
    }

    public function test_api_staff_reset_endpoint_success_and_error_json(): void
    {
        $successResponse = $this->postJson('/api/forgot-password/staff', [
            'username' => 'teller1',
            'full_name' => 'Budi Santoso',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $successResponse->assertOk();
        $successResponse->assertJson([
            'success' => true,
        ]);

        $errorResponse = $this->postJson('/api/forgot-password/staff', [
            'username' => 'teller1',
            'full_name' => 'Wrong Name',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $errorResponse->assertStatus(422);
        $errorResponse->assertJson([
            'success' => false,
        ]);
    }

    public function test_api_nasabah_reset_endpoint_success_and_error_json(): void
    {
        $successResponse = $this->postJson('/api/forgot-password/nasabah', [
            'student_number' => '2023001',
            'class_name' => 'X RPL 1',
            'pin' => '654321',
            'pin_confirmation' => '654321',
        ]);

        $successResponse->assertOk();
        $successResponse->assertJson([
            'success' => true,
        ]);

        $errorResponse = $this->postJson('/api/forgot-password/nasabah', [
            'student_number' => '2023001',
            'class_name' => 'Wrong Class',
            'pin' => '654321',
            'pin_confirmation' => '654321',
        ]);

        $errorResponse->assertStatus(422);
        $errorResponse->assertJson([
            'success' => false,
        ]);
    }
}
