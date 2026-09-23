<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RequestResetOtpRequest;
use App\Http\Requests\Auth\ResetCredentialRequest;
use App\Services\CredentialRecoveryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
        $result = $this->recovery->requestOtp(
            $request->validated('account_type'),
            $request->validated('email'),
        );

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => $result['ok'],
                'message' => $result['message'],
            ], $result['status']);
        }

        if (! $result['ok']) {
            return back()->withInput()->withErrors([$result['field'] => $result['message']]);
        }

        return back()
            ->withInput()
            ->with('success', $result['message'])
            ->with('otp_sent', true);
    }

    public function resetCredential(ResetCredentialRequest $request): JsonResponse|RedirectResponse
    {
        $accountType = $request->validated('account_type');
        $newSecret = $accountType === CredentialRecoveryService::TYPE_NASABAH
            ? $request->validated('pin')
            : $request->validated('password');

        $result = $this->recovery->verifyAndReset(
            $accountType,
            $request->validated('email'),
            $request->validated('otp'),
            $newSecret,
        );

        if ($this->wantsJson($request)) {
            return response()->json([
                'success' => $result['ok'],
                'message' => $result['message'],
            ], $result['status']);
        }

        if (! $result['ok']) {
            return back()
                ->withInput()
                ->withErrors([$result['field'] => $result['message']])
                ->with('otp_sent', true);
        }

        return redirect()->route('login')->with('success', $result['message']);
    }
}
