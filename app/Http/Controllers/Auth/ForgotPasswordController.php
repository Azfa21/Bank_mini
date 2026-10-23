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
        $result = $this->recovery->verifyAndReset(
            $request->validated('account_type'),
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
}
