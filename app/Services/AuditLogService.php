<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    public static function record(
        string $action,
        ?string $entityName = null,
        ?int $entityId = null,
        ?array $metadata = null,
        ?int $userId = null,
    ): AuditLog {
        $request = request();

        if ($userId === null && Auth::guard('web')->check()) {
            $userId = Auth::guard('web')->id();
        }

        return AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'entity_name' => $entityName,
            'entity_id' => $entityId,
            'ip_address' => $request->ip(),
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
