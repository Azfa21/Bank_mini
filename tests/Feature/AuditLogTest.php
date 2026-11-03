<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Saver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $teller;

    protected Saver $nasabah;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'username' => 'admin',
            'password_hash' => Hash::make('password'),
            'full_name' => 'Admin',
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->teller = User::create([
            'username' => 'teller1',
            'password_hash' => Hash::make('password'),
            'full_name' => 'Teller 1',
            'role' => 'teller',
            'status' => 'active',
        ]);

        $this->nasabah = Saver::create([
            'student_number' => '2023001',
            'full_name' => 'Ani',
            'pin_hash' => Hash::make('123456'),
            'password_hash' => Hash::make('password123'),
            'qr_code_value' => 'qr-test-1',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);
    }

    public function test_login_success_creates_audit_log(): void
    {
        $this->post('/login', ['identifier' => 'teller1', 'secret' => 'password']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login_success',
            'entity_name' => 'user',
            'entity_id' => $this->teller->id,
            'user_id' => $this->teller->id,
        ]);
    }

    public function test_nasabah_web_login_is_blocked_and_creates_audit_log(): void
    {
        $response = $this->post('/login', ['identifier' => '2023001', 'secret' => 'password123']);

        $response->assertSessionHasErrors(['identifier' => 'Akun penabung hanya dapat diakses melalui aplikasi mobile Bank Mini.']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'web_login_blocked',
            'entity_name' => 'saver',
            'entity_id' => $this->nasabah->id,
            'user_id' => null,
        ]);
    }

    public function test_login_failed_creates_audit_log(): void
    {
        $this->post('/login', ['identifier' => 'nobody', 'secret' => 'wrong']);

        $log = AuditLog::where('action', 'login_failed')->first();
        $this->assertNotNull($log);
        $this->assertNull($log->user_id);
    }

    public function test_login_blocked_creates_audit_log(): void
    {
        $blocked = User::create([
            'username' => 'blocked1',
            'password_hash' => Hash::make('password'),
            'full_name' => 'Blocked',
            'role' => 'teller',
            'status' => 'blocked',
        ]);

        $this->post('/login', ['identifier' => 'blocked1', 'secret' => 'password']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'login_blocked',
            'entity_name' => 'user',
            'entity_id' => $blocked->id,
        ]);
    }

    public function test_logout_creates_audit_log(): void
    {
        $this->actingAs($this->teller, 'web');
        $this->post('/logout');

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'logout',
            'user_id' => $this->teller->id,
        ]);
    }

    public function test_access_denied_creates_audit_log(): void
    {
        $this->actingAs($this->teller, 'web')->get('/admin/dashboard');

        $log = AuditLog::where('action', 'access_denied')
            ->where('user_id', $this->teller->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('admin/dashboard', $log->metadata['path']);
    }

    public function test_audit_log_records_ip_address(): void
    {
        $this->post('/login', ['identifier' => 'nobody', 'secret' => 'wrong']);

        $log = AuditLog::where('action', 'login_failed')->latest()->first();
        $this->assertNotNull($log->ip_address);
    }
}
