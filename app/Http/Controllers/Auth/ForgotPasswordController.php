<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RequestResetOtpRequest;
use App\Http\Requests\Auth\ResetCredentialRequest;
use App\Services\CredentialRecoveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function __construct(
        protected CredentialRecoveryService $recovery,
    ) {}

    public function showForgotForm(): View
    {
        return view('auth.forgot-password');
    }

    public function requestOtp(RequestResetOtpRequest $request): JsonResponse|RedirectResponse
    {
        $accountType = (string) $request->validated('account_type');

        if ($response = $this->rejectSaverRecoveryOutsideMobile($request, $accountType)) {
            return $response;
        }

        $result = $this->recovery->requestOtp(
            $accountType,
            $request->validated('identifier'),
        );

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => $result['ok'],
                'message' => $result['message'],
                'email' => $result['email'],
            ], $result['status']);
        }

        if (! $result['ok']) {
            return back()->withInput()->withErrors([$result['field'] => $result['message']]);
        }

        $redirect = back()
            ->withInput()
            ->with('success', $result['message'])
            ->with('otp_sent', true);

        if ($result['email'] !== null) {
            $redirect->with('email', $result['email']);
        }

        return $redirect;
    }

    public function resetCredential(ResetCredentialRequest $request): JsonResponse|RedirectResponse
    {
        $accountType = (string) $request->validated('account_type');

        if ($response = $this->rejectSaverRecoveryOutsideMobile($request, $accountType)) {
            return $response;
        }

        $result = $this->recovery->verifyAndReset(
            $accountType,
            $request->validated('identifier'),
            $request->validated('otp'),
            $request->validated('password'),
        );

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => $result['ok'],
                'message' => $result['message'],
                'email' => $result['email'],
            ], $result['status']);
        }

        if (! $result['ok']) {
            $redirect = back()
                ->withInput()
                ->withErrors([$result['field'] => $result['message']])
                ->with('otp_sent', true);

            if ($result['email'] !== null) {
                $redirect->with('email', $result['email']);
            }

            return $redirect;
        }

        return redirect()->route('login')->with('success', $result['message']);
    }

    private function rejectSaverRecoveryOutsideMobile(Request $request, string $accountType): JsonResponse|RedirectResponse|null
    {
        if ($accountType !== CredentialRecoveryService::TYPE_NASABAH || $request->routeIs('api.mobile.forgot-password.*')) {
            return null;
        }

        $message = 'Pemulihan password penabung hanya tersedia di aplikasi mobile Bank Mini.';

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'email' => null,
            ], 403);
        }

        return redirect()->route('password.request')->with('error', $message);
    }
}
