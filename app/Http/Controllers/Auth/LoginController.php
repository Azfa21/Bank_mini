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

                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json(['success' => false, 'message' => 'Akun Anda diblokir.'], 403);
                }

                return back()->withErrors(['identifier' => 'Akun Anda diblokir.']);
            }

            $request->session()->regenerate();
            AuditLogService::record('login_success', 'user', $user->id, ['role' => $user->role], $user->id);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'user' => [
                        'id' => $user->id,
                        'full_name' => $user->full_name,
                        'username' => $user->username,
                        'role' => $user->role,
                    ],
                ]);
            }

            return $this->redirectByRole($user->role);
        }

        if (Auth::guard('nasabah')->attempt(['student_number' => $identifier, 'password' => $secret])) {
            $nasabah = Auth::guard('nasabah')->user();

            if ($nasabah->status !== 'active') {
                Auth::guard('nasabah')->logout();
                AuditLogService::record('login_blocked', 'customer', $nasabah->id, ['student_number' => $nasabah->student_number]);

                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json(['success' => false, 'message' => 'Akun Anda diblokir.'], 403);
                }

                return back()->withErrors(['identifier' => 'Akun Anda diblokir.']);
            }

            $request->session()->regenerate();
            AuditLogService::record('login_success', 'customer', $nasabah->id, ['student_number' => $nasabah->student_number]);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Login berhasil.',
                    'customer' => [
                        'id' => $nasabah->id,
                        'full_name' => $nasabah->full_name,
                        'student_number' => $nasabah->student_number,
                        'role' => 'nasabah',
                    ],
                ]);
            }

            return redirect()->route('nasabah.dashboard');
        }

        AuditLogService::record('login_failed', null, null, ['identifier' => $identifier]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => false, 'message' => 'Kredensial tidak valid.'], 401);
        }

        return back()->withErrors(['identifier' => 'Kredensial tidak valid.']);
    }

    public function logout(Request $request)
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

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => true, 'message' => 'Logout berhasil.']);
        }

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
