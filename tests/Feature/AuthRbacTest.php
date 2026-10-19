<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\DailyReport;
use App\Models\JurnalAkuntansi;
use App\Models\Saver;
use App\Models\SystemConfig;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthRbacTest extends TestCase
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
            'qr_code_value' => 'qr-test-1',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);
    }

    public function test_teller_accessing_admin_dashboard_redirects_to_login(): void
    {
        $response = $this->actingAs($this->teller, 'web')->get('/admin/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_nasabah_accessing_admin_dashboard_redirects_to_login(): void
    {
        $response = $this->actingAs($this->nasabah, 'nasabah')->get('/admin/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_nasabah_accessing_teller_dashboard_redirects_to_login(): void
    {
        $response = $this->actingAs($this->nasabah, 'nasabah')->get('/teller/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_teller_can_access_own_dashboard(): void
    {
        $this->actingAs($this->teller, 'web')
            ->get('/teller/dashboard')
            ->assertOk();
    }

    public function test_admin_can_access_own_dashboard(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get('/admin/dashboard')
            ->assertOk();
    }

    public function test_nasabah_can_access_own_dashboard(): void
    {
        $this->actingAs($this->nasabah, 'nasabah')
            ->get('/nasabah/dashboard')
            ->assertOk();
    }

    public function test_admin_is_forbidden_from_creating_transactions(): void
    {
        $this->actingAs($this->admin, 'web');

        $this->assertFalse(Gate::allows('create', Transaction::class));
    }

    public function test_teller_is_allowed_to_create_transactions(): void
    {
        $this->actingAs($this->teller, 'web');

        $this->assertTrue(Gate::allows('create', Transaction::class));
    }

    public function test_admin_is_forbidden_from_creating_journal_entries(): void
    {
        $this->actingAs($this->admin, 'web');

        $this->assertFalse(Gate::allows('create', JurnalAkuntansi::class));
        $this->assertFalse(Gate::allows('update', JurnalAkuntansi::class));
        $this->assertFalse(Gate::allows('delete', JurnalAkuntansi::class));
    }

    public function test_teller_has_no_access_to_journal_entries_at_all(): void
    {
        $this->actingAs($this->teller, 'web');

        $this->assertFalse(Gate::allows('view', JurnalAkuntansi::class));
        $this->assertFalse(Gate::allows('viewAny', JurnalAkuntansi::class));
    }

    public function test_admin_can_view_journal_entries(): void
    {
        $this->actingAs($this->admin, 'web');
        $this->assertTrue(Gate::allows('viewAny', JurnalAkuntansi::class));
    }

    public function test_login_with_wrong_username_or_password_is_rejected(): void
    {
        $response = $this->post('/login', [
            'identifier' => 'teller1',
            'secret' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('identifier');
        $this->assertGuest('web');
        $this->assertGuest('nasabah');
    }

    public function test_login_with_wrong_student_number_or_pin_is_rejected_with_same_message(): void
    {
        $wrongStaff = $this->post('/login', [
            'identifier' => 'teller1',
            'secret' => 'wrong-password',
        ]);

        $wrongNasabah = $this->post('/login', [
            'identifier' => '2023001',
            'secret' => '000000',
        ]);

        $wrongStaff->assertSessionHasErrors(['identifier' => 'Kredensial tidak valid.']);
        $wrongNasabah->assertSessionHasErrors(['identifier' => 'Kredensial tidak valid.']);
        $this->assertGuest('web');
        $this->assertGuest('nasabah');
    }

    public function test_teller_login_success_redirects_to_teller_dashboard(): void
    {
        $response = $this->post('/login', [
            'identifier' => 'teller1',
            'secret' => 'password',
        ]);

        $response->assertRedirect(route('teller.dashboard'));
        $this->assertAuthenticated('web');
        $this->assertGuest('nasabah');
    }

    public function test_nasabah_login_success_redirects_to_nasabah_dashboard(): void
    {
        $response = $this->post('/login', [
            'identifier' => '2023001',
            'secret' => '123456',
        ]);

        $response->assertRedirect(route('nasabah.dashboard'));
        $this->assertAuthenticated('nasabah');
    }

    public function test_user_password_hash_is_bcrypt_not_plaintext(): void
    {
        $this->post('/login', ['identifier' => 'teller1', 'secret' => 'password']);

        $hash = $this->teller->fresh()->password_hash;

        $this->assertStringStartsWith('$2y$', $hash);
        $this->assertNotSame('password', $hash);
    }

    public function test_customer_pin_hash_is_bcrypt_not_plain_pin(): void
    {
        $this->post('/login', ['identifier' => '2023001', 'secret' => '123456']);

        $hash = $this->nasabah->fresh()->pin_hash;

        $this->assertStringStartsWith('$2y$', $hash);
        $this->assertNotSame('123456', $hash);
    }

    public function test_logout_destroys_session(): void
    {
        $this->actingAs($this->teller, 'web');

        $this->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest('web');
    }

    public function test_user_policy_matrix(): void
    {
        $this->actingAs($this->admin, 'web');
        $this->assertTrue(Gate::allows('create', User::class));
        $this->assertTrue(Gate::allows('viewAny', User::class));
        $this->assertTrue(Gate::allows('view', $this->teller));
        $this->assertTrue(Gate::allows('update', $this->teller));
        $this->assertTrue(Gate::allows('delete', $this->teller));

        $this->actingAs($this->teller, 'web');
        $this->assertFalse(Gate::allows('viewAny', User::class));
        $this->assertFalse(Gate::allows('view', $this->admin));
        $this->assertFalse(Gate::allows('create', User::class));
    }

    public function test_account_policy_matrix(): void
    {
        $account = Account::create([
            'account_number' => 'TAB-TEST',
            'saver_id' => $this->nasabah->id,
            'balance' => 25000,
            'status' => 'active',
            'opened_at' => now(),
        ]);

        $this->actingAs($this->admin, 'web');
        $this->assertTrue(Gate::allows('create', Account::class));
        $this->assertTrue(Gate::allows('view', $account));
        $this->assertTrue(Gate::allows('update', $account));

        $this->actingAs($this->teller, 'web');
        $this->assertTrue(Gate::allows('view', $account));
        $this->assertFalse(Gate::allows('create', Account::class));
        $this->assertFalse(Gate::allows('update', $account));

        $this->actingAs($this->nasabah, 'nasabah');
        $this->assertFalse(Gate::allows('view', $account));
    }

    public function test_daily_report_policy_matrix(): void
    {
        $report = DailyReport::create([
            'teller_id' => $this->teller->id,
            'report_date' => now()->toDateString(),
            'opening_balance' => 100000,
            'total_deposit' => 0,
            'total_withdrawal' => 0,
            'closing_balance_system' => 100000,
            'status' => 'draft',
        ]);

        $this->actingAs($this->teller, 'web');
        $this->assertTrue(Gate::allows('create', DailyReport::class));
        $this->assertTrue(Gate::allows('view', $report));
        $this->assertFalse(Gate::allows('approve', $report));

        $this->actingAs($this->admin, 'web');
        $this->assertTrue(Gate::allows('view', $report));
        $this->assertTrue(Gate::allows('approve', $report));
        $this->assertFalse(Gate::allows('create', DailyReport::class));

        $this->actingAs($this->nasabah, 'nasabah');
        $this->assertFalse(Gate::allows('view', $report));
    }

    public function test_system_config_policy_matrix(): void
    {
        $config = SystemConfig::create([
            'config_key' => 'minimum_balance',
            'config_value' => '10000',
        ]);

        $this->actingAs($this->admin, 'web');
        $this->assertTrue(Gate::allows('view', $config));
        $this->assertTrue(Gate::allows('update', $config));

        foreach ([$this->teller] as $user) {
            $this->actingAs($user, 'web');
            $this->assertFalse(Gate::allows('view', $config));
            $this->assertFalse(Gate::allows('update', $config));
        }
    }
}
