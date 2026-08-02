<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $identifier = $request->input('identifier', '');
        $secret = $request->input('secret', '');

        $secretRules = ['required', 'string'];
        if (preg_match('/^\d+$/', $identifier)) {
            $secretRules[] = 'digits:6';
        }

        $request->validate([
            'identifier' => ['required', 'string'],
            'secret' => $secretRules,
        ]);

        if (Auth::guard('web')->attempt(['username' => $identifier, 'password' => $secret])) {
            $user = Auth::guard('web')->user();

            if ($user->status !== 'active') {
                Auth::guard('web')->logout();
                AuditLogService::record('login_blocked', 'user', $user->id, ['username' => $user->username]);

                return back()->withErrors(['identifier' => 'Akun Anda diblokir.']);
            }

            $request->session()->regenerate();
            AuditLogService::record('login_success', 'user', $user->id, ['role' => $user->role], $user->id);

            return $this->redirectByRole($user->role);
        }

        if (Auth::guard('nasabah')->attempt(['student_number' => $identifier, 'password' => $secret])) {
            $nasabah = Auth::guard('nasabah')->user();

            if ($nasabah->status !== 'active') {
                Auth::guard('nasabah')->logout();
                AuditLogService::record('login_blocked', 'customer', $nasabah->id, ['student_number' => $nasabah->student_number]);

                return back()->withErrors(['identifier' => 'Akun Anda diblokir.']);
            }

            $request->session()->regenerate();
            AuditLogService::record('login_success', 'customer', $nasabah->id, ['student_number' => $nasabah->student_number]);

            return redirect()->route('nasabah.dashboard');
        }

        AuditLogService::record('login_failed', null, null, ['identifier' => $identifier]);

        return back()->withErrors(['identifier' => 'Kredensial tidak valid.']);
    }

    public function logout(Request $request): RedirectResponse
    {
        $staffId = Auth::guard('web')->id();
        $nasabah = Auth::guard('nasabah')->user();

        if ($staffId !== null) {
            AuditLogService::record('logout', 'user', $staffId, [], $staffId);
        } elseif ($nasabah !== null) {
            AuditLogService::record('logout', 'customer', $nasabah->id, ['student_number' => $nasabah->student_number]);
        }

        Auth::guard('web')->logout();
        Auth::guard('nasabah')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function redirectByRole(string $role): RedirectResponse
    {
        return match ($role) {
            'admin' => redirect()->route('admin.dashboard'),
            'teller' => redirect()->route('teller.dashboard'),
            'supervisor' => redirect()->route('supervisor.dashboard'),
            default => redirect()->route('login'),
        };
    }
}
