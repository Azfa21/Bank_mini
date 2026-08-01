<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
        $credentials = $request->validate([
            'identifier' => ['required', 'string'],
            'secret' => ['required', 'string'],
        ]);

        $identifier = $credentials['identifier'];
        $secret = $credentials['secret'];

        if (Auth::guard('web')->attempt(['username' => $identifier, 'password' => $secret])) {
            $request->session()->regenerate();

            return $this->redirectByRole(Auth::guard('web')->user()->role);
        }

        if (Auth::guard('nasabah')->attempt(['student_number' => $identifier, 'password' => $secret])) {
            $request->session()->regenerate();

            return redirect()->route('nasabah.dashboard');
        }

        return back()->withErrors(['identifier' => 'Kredensial tidak valid.']);
    }

    public function logout(Request $request): RedirectResponse
    {
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
