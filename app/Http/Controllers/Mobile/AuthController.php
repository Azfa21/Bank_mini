<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\MobileToken;
use App\Models\Saver;
use App\Services\AuditLogService;
use App\Services\MobileTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected MobileTokenService $tokens,
    ) {}

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'student_number' => ['required', 'string'],
            'pin' => ['required', 'string', 'digits:6'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ], [
            'student_number.required' => 'Nomor siswa (NIS) wajib diisi.',
            'pin.required' => 'PIN wajib diisi.',
            'pin.digits' => 'PIN harus tepat 6 digit angka.',
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
                'student_number' => 'Nomor siswa atau PIN tidak valid.',
            ]);
        }

        if ($customer->status !== 'active') {
            AuditLogService::record('mobile_login_blocked', 'saver', $customer->id, [
                'student_number' => $customer->student_number,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Akun rekening Anda diblokir. Silakan hubungi loket teller atau Administrator.',
            ], 403);
        }

        if ($customer->isPinLocked()) {
            AuditLogService::record('mobile_login_locked', 'saver', $customer->id, [
                'student_number' => $customer->student_number,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'PIN terkunci sementara karena 3x salah. Coba lagi setelah '
                    .$customer->locked_until->diffForHumans().' atau lakukan reset PIN.',
            ], 423);
        }

        if (! Hash::check($data['pin'], $customer->pin_hash)) {
            return $this->handleFailedPin($customer);
        }

        if ((int) $customer->failed_pin_attempts > 0 || $customer->locked_until !== null) {
            $customer->update([
                'failed_pin_attempts' => 0,
                'locked_until' => null,
            ]);
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

    protected function handleFailedPin(Saver $customer): JsonResponse
    {
        $attempts = (int) $customer->failed_pin_attempts + 1;

        if ($attempts >= 3) {
            $customer->update([
                'failed_pin_attempts' => $attempts,
                'locked_until' => now()->addMinutes(15),
            ]);

            AuditLogService::record('customer_pin_locked', 'saver', $customer->id, [
                'student_number' => $customer->student_number,
                'action' => 'mobile_login',
                'failed_attempts' => $attempts,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'PIN salah 3 kali berturut-turut. Akun dikunci selama 15 menit.',
            ], 423);
        }

        $customer->update(['failed_pin_attempts' => $attempts]);

        AuditLogService::record('mobile_login_failed', 'saver', $customer->id, [
            'student_number' => $customer->student_number,
            'reason' => 'invalid_pin',
            'failed_attempts' => $attempts,
        ]);

        return response()->json([
            'success' => false,
            'message' => 'PIN salah. Percobaan ke-'.$attempts.' dari 3.',
        ], 401);
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
