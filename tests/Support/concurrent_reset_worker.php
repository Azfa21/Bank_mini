<?php

use App\Services\CredentialRecoveryService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

[$token, $barrier, $readyFile, $accountType, $identifier, $otp, $password, $connectionName, $connectionConfig] = array_slice($argv, 1);

config([
    'mail.default' => 'array',
    'cache.default' => 'array',
    'session.driver' => 'array',
    'queue.default' => 'sync',
    'database.default' => $connectionName,
    'database.connections.'.$connectionName => json_decode($connectionConfig, true),
]);

DB::purge($connectionName);
DB::setDefaultConnection($connectionName);

file_put_contents($readyFile, (string) getmypid());

$deadline = microtime(true) + 20.0;

while (! file_exists($barrier)) {
    if (microtime(true) > $deadline) {
        echo json_encode(['ok' => false, 'status' => 504, 'error' => 'barrier_timeout']);

        return 1;
    }

    usleep(2000);
}

try {
    $result = app(CredentialRecoveryService::class)->verifyAndReset(
        $accountType,
        $identifier,
        $otp,
        Hash::make($password),
    );
} catch (Throwable $exception) {
    echo json_encode([
        'ok' => false,
        'status' => 500,
        'error' => $exception::class.': '.$exception->getMessage(),
    ]);

    return 1;
}

echo json_encode([
    'token' => $token,
    'ok' => (bool) $result['ok'],
    'status' => (int) $result['status'],
    'field' => $result['field'],
    'message' => $result['message'],
]);
