<?php

namespace App\Http\Middleware;

use App\Services\MobileTokenService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateMobile
{
    public function __construct(
        protected MobileTokenService $tokens,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->bearerToken();

        if (! is_string($plain) || $plain === '') {
            return response()->json([
                'success' => false,
                'message' => 'Token autentikasi tidak ditemukan.',
            ], 401);
        }

        $token = $this->tokens->resolve($plain);

        if ($token === null) {
            return response()->json([
                'success' => false,
                'message' => 'Sesi telah berakhir. Silakan login kembali.',
            ], 401);
        }

        $customer = $token->saver;

        if ($customer === null || $customer->status !== 'active') {
            $this->tokens->revoke($plain);

            return response()->json([
                'success' => false,
                'message' => 'Akun rekening Anda diblokir. Silakan hubungi loket teller atau Administrator.',
            ], 403);
        }

        Auth::guard('nasabah')->setUser($customer);
        $request->attributes->set('mobile_token', $token);
        $request->attributes->set('mobile_customer', $customer);

        return $next($request);
    }
}
