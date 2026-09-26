<?php

namespace App\Services;

use App\Models\MobileToken;
use App\Models\Saver;

class MobileTokenService
{
    public const DEFAULT_TTL_DAYS = 30;

    /**
     * @return array{plain: string, token: MobileToken}
     */
    public function issue(Saver $saver, string $name = 'android'): array
    {
        $plain = bin2hex(random_bytes(32));

        $token = MobileToken::create([
            'saver_id' => $saver->id,
            'name' => $name,
            'token_hash' => $this->hash($plain),
            'expires_at' => now()->addDays(self::DEFAULT_TTL_DAYS),
            'created_at' => now(),
        ]);

        return ['plain' => $plain, 'token' => $token];
    }

    public function resolve(string $plain): ?MobileToken
    {
        $token = MobileToken::query()
            ->where('token_hash', $this->hash($plain))
            ->first();

        if ($token === null || $token->isExpired()) {
            return null;
        }

        $token->forceFill(['last_used_at' => now()])->save();

        return $token;
    }

    public function revoke(string $plain): void
    {
        MobileToken::query()->where('token_hash', $this->hash($plain))->delete();
    }

    public function revokeAll(Saver $saver): void
    {
        MobileToken::query()->where('saver_id', $saver->id)->delete();
    }

    public function hash(string $plain): string
    {
        return hash('sha256', $plain);
    }
}
