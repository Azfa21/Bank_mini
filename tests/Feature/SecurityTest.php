<?php

namespace Tests\Feature;

use App\Models\Saver;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

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

    public function test_blocked_user_cannot_login(): void
    {
        User::create([
            'username' => 'blocked1',
            'password_hash' => Hash::make('password'),
            'full_name' => 'Blocked Teller',
            'role' => 'teller',
            'status' => 'blocked',
        ]);

        $this->post('/login', ['identifier' => 'blocked1', 'secret' => 'password'])
            ->assertSessionHasErrors('identifier');

        $this->assertGuest('web');
    }

    public function test_blocked_nasabah_cannot_login(): void
    {
        Saver::create([
            'student_number' => '2023999',
            'full_name' => 'Blocked Nasabah',
            'pin_hash' => Hash::make('123456'),
            'password_hash' => Hash::make('password123'),
            'qr_code_value' => 'qr-blocked-1',
            'created_by' => $this->admin->id,
            'status' => 'blocked',
        ]);

        $this->post('/login', ['identifier' => '2023999', 'secret' => 'password123'])
            ->assertSessionHasErrors('identifier');

        $this->assertGuest('nasabah');
    }

    public function test_blocked_user_cannot_access_dashboard_even_if_authenticated(): void
    {
        $blocked = User::create([
            'username' => 'blocked2',
            'password_hash' => Hash::make('password'),
            'full_name' => 'Blocked Admin',
            'role' => 'admin',
            'status' => 'blocked',
        ]);

        $this->actingAs($blocked, 'web')
            ->get('/admin/dashboard')
            ->assertRedirect(route('login'));
    }

    public function test_login_is_throttled_after_too_many_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['identifier' => 'nobody', 'secret' => 'wrong']);
        }

        $this->post('/login', ['identifier' => 'nobody', 'secret' => 'wrong'])
            ->assertStatus(429);
    }

    public function test_nasabah_is_allowed_on_multi_role_route_that_lists_it(): void
    {
        Route::middleware('role:admin,nasabah')
            ->get('/_security/multi-role', fn () => 'ok');

        $this->actingAs($this->nasabah, 'nasabah')
            ->get('/_security/multi-role')
            ->assertOk();

        $this->app['auth']->guard('nasabah')->logout();

        $this->actingAs($this->admin, 'web')
            ->get('/_security/multi-role')
            ->assertOk();
    }

    public function test_nasabah_is_denied_from_multi_role_route_without_its_role(): void
    {
        Route::middleware('role:admin,teller')
            ->get('/_security/multi-role-excluded', fn () => 'ok');

        $this->actingAs($this->nasabah, 'nasabah')
            ->get('/_security/multi-role-excluded')
            ->assertRedirect(route('login'));
    }

    public function test_security_headers_are_present_on_responses(): void
    {
        $this->get('/login')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()')
            ->assertHeader('Content-Security-Policy', "default-src 'self'")
            ->assertHeaderContains('Cache-Control', 'no-store')
            ->assertHeaderContains('Cache-Control', 'no-cache')
            ->assertHeaderContains('Cache-Control', 'must-revalidate');
    }

    public function test_authenticated_page_is_not_cached(): void
    {
        $this->actingAs($this->admin, 'web')
            ->get('/admin/dashboard')
            ->assertHeaderContains('Cache-Control', 'no-store');
    }

    public function test_customer_pin_hash_is_auto_hashed_on_update(): void
    {
        $this->nasabah->update(['pin_hash' => '123456']);

        $stored = $this->nasabah->fresh()->pin_hash;

        $this->assertStringStartsWith('$2y$', $stored);
        $this->assertNotSame('12345678', $stored);
    }
}
