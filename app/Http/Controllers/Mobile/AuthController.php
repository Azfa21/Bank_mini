<?php

namespace App\Http\Controllers\Mobile;

use App\Exceptions\TransactionValidationException;
use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\MobileToken;
use App\Models\Saver;
use App\Services\AuditLogService;
use App\Services\MobileTokenService;
use App\Services\SaverCredentialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected MobileTokenService $tokens,
        protected SaverCredentialService $credentials,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_number' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ], [
            'student_number.required' => 'Nomor siswa (NIS) wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
        ]);

        $customer = Saver::query()
            ->where('student_number', $data['student_number'])
            ->first();

        if ($customer === null) {
            AuditLogService::record('mobile_login_failed', 'saver', null, [
                'student_number' => $data['student_number'],
                'reason' => 'not_found',
            ]);

            throw ValidationException::withMessages([
                'student_number' => 'Nomor siswa atau password tidak valid.',
            ]);
        }

        if ($customer->status !== 'active') {
            AuditLogService::record('mobile_login_blocked', 'saver', $customer->id, [
                'student_number' => $customer->student_number,
            ]);

            return response()->json([
                'success' => false,
                'message' => $customer->isGraduated()
                    ? 'Rekening Anda sudah ditutup karena kelulusan. Hubungi Administrator bila masih ada sisa saldo.'
                    : 'Akun rekening Anda diblokir. Silakan hubungi loket teller atau Administrator.',
            ], 403);
        }

        if (! Hash::check($data['password'], $customer->password_hash ?? '')) {
            AuditLogService::record('mobile_login_failed', 'saver', $customer->id, [
                'student_number' => $customer->student_number,
                'reason' => 'invalid_password',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Nomor siswa atau password tidak valid.',
            ], 401);
        }

        $issued = $this->tokens->issue($customer, $data['device_name'] ?? 'android');

        AuditLogService::record('mobile_login_success', 'saver', $customer->id, [
            'student_number' => $customer->student_number,
            'token_id' => $issued['token']->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'data' => [
                'token' => $issued['plain'],
                'expires_at' => $issued['token']->expires_at?->toIso8601String(),
                'customer' => $this->saverPayload($customer),
                'account' => $this->accountPayload($customer->account),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $customer = $this->saver($request);

        /** @var MobileToken|null $token */
        $token = $request->attributes->get('mobile_token');

        if ($token !== null) {
            $token->delete();
        }

        AuditLogService::record('mobile_logout', 'saver', $customer->id, [
            'student_number' => $customer->student_number,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $customer = $this->saver($request);

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => $this->saverPayload($customer),
                'account' => $this->accountPayload($customer->account),
            ],
        ]);
    }

    /**
     * Ganti PIN transaksi. Sesi token yang sedang dipakai tidak ikut dicabut
     * karena PIN tidak dipakai untuk masuk aplikasi.
     */
    public function changePin(Request $request): JsonResponse
    {
        $customer = $this->saver($request);

        $data = $request->validate([
            'current_pin' => ['required', 'string', 'digits:6'],
            'pin' => ['required', 'string', 'digits:6', 'different:current_pin', 'confirmed'],
            'pin_confirmation' => ['required', 'string', 'digits:6'],
        ], [
            'current_pin.required' => 'PIN saat ini wajib diisi.',
            'current_pin.digits' => 'PIN saat ini harus tepat 6 digit angka.',
            'pin.required' => 'PIN baru wajib diisi.',
            'pin.digits' => 'PIN baru harus tepat 6 digit angka.',
            'pin.different' => 'PIN baru tidak boleh sama dengan PIN saat ini.',
            'pin.confirmed' => 'Konfirmasi PIN baru tidak cocok.',
            'pin_confirmation.required' => 'Konfirmasi PIN baru wajib diisi.',
            'pin_confirmation.digits' => 'Konfirmasi PIN baru harus tepat 6 digit angka.',
        ]);

        try {
            $this->credentials->changeTransactionPin($customer, $data['current_pin'], $data['pin']);
        } catch (TransactionValidationException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'PIN transaksi berhasil diperbarui.',
        ]);
    }

    protected function saver(Request $request): Saver
    {
        /** @var Saver $customer */
        $customer = $request->attributes->get('mobile_customer');

        return $customer;
    }

    /**
     * @return array<string, mixed>
     */
    protected function saverPayload(Saver $customer): array
    {
        return [
            'id' => $customer->id,
            'student_number' => $customer->student_number,
            'full_name' => $customer->full_name,
            'class_name' => $customer->class_name,
            'phone_number' => $customer->phone_number,
            'qr_code_value' => $customer->qr_code_value,
            'status' => $customer->status,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function accountPayload(?Account $account): ?array
    {
        if ($account === null) {
            return null;
        }

        return [
            'id' => $account->id,
            'account_number' => $account->account_number,
            'academic_year' => $account->academic_year,
            'opening_balance' => (int) $account->opening_balance,
            'balance' => (int) $account->balance,
            'minimum_balance' => (int) $account->minimum_balance,
            'status' => $account->status,
            'opened_at' => optional($account->opened_at)->toIso8601String(),
        ];
    }
}
