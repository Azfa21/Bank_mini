<?php

namespace App\Http\Middleware;

use App\Services\AuditLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (Auth::guard('nasabah')->check()) {
            $nasabah = Auth::guard('nasabah')->user();

            if ($nasabah->status === 'active' && in_array('nasabah', $roles, true)) {
                return $next($request);
            }

            AuditLogService::record('access_denied', 'saver', $nasabah->id, [
                'path' => $request->path(),
                'roles' => $roles,
                'student_number' => $nasabah->student_number,
            ]);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke endpoint ini.',
                ], 403);
            }

            return redirect()->route('login')->with('error', 'Anda tidak memiliki akses ke halaman ini.');
        }

        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();

            if ($user->status === 'active' && in_array($user->role, $roles)) {
                return $next($request);
            }

            AuditLogService::record('access_denied', 'user', $user->id, [
                'path' => $request->path(),
                'roles' => $roles,
                'role' => $user->role,
            ], $user->id);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke endpoint ini.',
                ], 403);
            }

            return redirect()->route('login')->with('error', 'Anda tidak memiliki akses ke halaman ini.');
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        return redirect()->route('login')->with('error', 'Anda tidak memiliki akses ke halaman ini.');
    }
}
