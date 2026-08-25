<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function showForgotForm(): View
    {
        return view('auth.forgot-password');
    }

    public function resetStaffPassword(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'full_name' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'full_name.required' => 'Nama lengkap terdaftar wajib diisi.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal harus 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        $user = User::where('username', $request->input('username'))->first();

        if (! $user || strcasecmp(trim($user->full_name), trim($request->input('full_name'))) !== 0) {
            AuditLogService::record('password_reset_failed', 'user', null, [
                'username' => $request->input('username'),
                'reason' => 'invalid_identity',
            ]);

            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data verifikasi identitas pegawai tidak sesuai.',
                ], 422);
            }

            return back()->withInput()->withErrors([
                'username' => 'Data identitas pegawai tidak sesuai dengan catatan sistem.',
            ]);
        }

        if ($user->status !== 'active') {
            AuditLogService::record('password_reset_blocked', 'user', $user->id, [
                'username' => $user->username,
            ]);

            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun Anda diblokir. Silakan hubungi Administrator.',
                ], 403);
            }

            return back()->withInput()->withErrors([
                'username' => 'Akun Anda diblokir. Silakan hubungi Administrator.',
            ]);
        }

        $user->update([
            'password_hash' => $request->input('password'),
        ]);

        AuditLogService::record('password_reset_success', 'user', $user->id, [
            'username' => $user->username,
            'role' => $user->role,
        ], $user->id);

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'Password berhasil diperbarui. Silakan masuk menggunakan password baru.',
            ]);
        }

        return redirect()->route('login')->with('success', 'Password pegawai berhasil diperbarui. Silakan login kembali.');
    }

    public function resetNasabahPin(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'student_number' => ['required', 'string'],
            'class_name' => ['required', 'string'],
            'pin' => ['required', 'string', 'digits:6', 'confirmed'],
        ], [
            'student_number.required' => 'Nomor siswa (NIS) wajib diisi.',
            'class_name.required' => 'Kelas terdaftar wajib diisi.',
            'pin.required' => 'PIN baru wajib diisi.',
            'pin.digits' => 'PIN baru harus tepat 6 digit angka.',
            'pin.confirmed' => 'Konfirmasi PIN baru tidak cocok.',
        ]);

        $customer = Customer::where('student_number', $request->input('student_number'))->first();

        if (! $customer || strcasecmp(trim($customer->class_name ?? ''), trim($request->input('class_name'))) !== 0) {
            AuditLogService::record('pin_reset_failed', 'customer', null, [
                'student_number' => $request->input('student_number'),
                'reason' => 'invalid_identity',
            ]);

            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Data verifikasi nasabah / siswa tidak sesuai.',
                ], 422);
            }

            return back()->withInput()->withErrors([
                'student_number' => 'Data siswa/kelas tidak sesuai dengan catatan sistem.',
            ]);
        }

        if ($customer->status !== 'active') {
            AuditLogService::record('pin_reset_blocked', 'customer', $customer->id, [
                'student_number' => $customer->student_number,
            ]);

            if ($this->wantsJson($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun rekening Anda diblokir. Silakan hubungi loket teller atau Administrator.',
                ], 403);
            }

            return back()->withInput()->withErrors([
                'student_number' => 'Akun rekening Anda diblokir. Silakan hubungi loket teller atau Administrator.',
            ]);
        }

        $customer->update([
            'pin_hash' => $request->input('pin'),
            'failed_pin_attempts' => 0,
            'locked_until' => null,
        ]);

        AuditLogService::record('pin_reset_success', 'customer', $customer->id, [
            'student_number' => $customer->student_number,
        ]);

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => true,
                'message' => 'PIN nasabah berhasil diperbarui. Silakan masuk menggunakan PIN baru.',
            ]);
        }

        return redirect()->route('login')->with('success', 'PIN nasabah berhasil diperbarui. Silakan login kembali.');
    }
}
