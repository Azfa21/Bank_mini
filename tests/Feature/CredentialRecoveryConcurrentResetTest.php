<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class CredentialRecoveryConcurrentResetTest extends TestCase
{
    private const WINNER_PASSWORD = 'WinnerPass123!';

    private const LOSER_PASSWORD = 'LoserPass123!';

    private const OTP = '482913';

    private const EMAIL = 'concurrent-staff@example.test';

    private const USERNAME = 'race-teller';

    private const ACCOUNT_ID = 4242;

    private ?string $connectionName = null;

    private string $sqlitePath = '';

    protected function setUp(): void
    {
        parent::setUp();

        if (! function_exists('proc_open')) {
            $this->markTestSkipped('proc_open is unavailable; concurrent replay test cannot run.');
        }

        $this->connectionName = 'recovery_race';

        config([
            'database.connections.'.$this->connectionName => $this->connectionConfig(),
        ]);

        DB::purge($this->connectionName);
        DB::setDefaultConnection($this->connectionName);

        $this->artisan('migrate', [
            '--database' => $this->connectionName,
            '--force' => true,
        ]);
    }

    protected function tearDown(): void
    {
        if ($this->connectionName !== null) {
            DB::purge($this->connectionName);
        }

        if ($this->sqlitePath !== '' && is_file($this->sqlitePath)) {
            @unlink($this->sqlitePath);
        }

        parent::tearDown();
    }

    #[Test]
    public function concurrent_replay_of_one_otp_permits_exactly_one_credential_update(): void
    {
        $this->seedAccountAndOtp();

        $results = $this->runWorkersConcurrently([
            ['token' => 'winner', 'password' => self::WINNER_PASSWORD],
            ['token' => 'loser', 'password' => self::LOSER_PASSWORD],
        ]);

        $succeeded = array_values(array_filter($results, fn (array $r) => $r['ok'] === true));
        $failed = array_values(array_filter($results, fn (array $r) => $r['ok'] !== true));

        $this->assertCount(1, $succeeded, 'exactly one concurrent replay may succeed');
        $this->assertCount(1, $failed, 'the competing replay must be rejected');

        $this->assertSame(200, $succeeded[0]['status']);
        $this->assertContains($failed[0]['status'], [409, 422]);

        $storedHash = (string) DB::connection($this->connectionName)
            ->table('users')
            ->where('email', self::EMAIL)
            ->value('password_hash');

        $this->assertPasswordMatchesWorkerWinner($storedHash, $results);

        $successAudits = (int) DB::connection($this->connectionName)
            ->table('audit_logs')
            ->where('action', 'password_reset_success')
            ->count();

        $this->assertSame(1, $successAudits, 'exactly one success audit entry is permitted');

        $consumedAt = DB::connection($this->connectionName)
            ->table('password_reset_otps')
            ->where('email', self::EMAIL)
            ->value('consumed_at');

        $this->assertNotNull($consumedAt, 'the shared OTP must be consumed exactly once');
    }

    private function connectionConfig(): array
    {
        if (env('RECOVERY_RACE_MYSQL') === '1') {
            return [
                'driver' => 'mysql',
                'host' => (string) env('RECOVERY_RACE_DB_HOST', '127.0.0.1'),
                'port' => (string) env('RECOVERY_RACE_DB_PORT', '3306'),
                'database' => (string) env('RECOVERY_RACE_DB_DATABASE'),
                'username' => (string) env('RECOVERY_RACE_DB_USERNAME', 'root'),
                'password' => (string) env('RECOVERY_RACE_DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => 'InnoDB',
            ];
        }

        $this->sqlitePath = sys_get_temp_dir().DIRECTORY_SEPARATOR
            .'bank_mini_recovery_race_'.bin2hex(random_bytes(6)).'.sqlite';

        touch($this->sqlitePath);

        return [
            'driver' => 'sqlite',
            'database' => $this->sqlitePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ];
    }

    private function seedAccountAndOtp(): void
    {
        $connection = DB::connection($this->connectionName);
        $now = now()->toDateTimeString();

        $connection->table('users')->insert([
            'id' => self::ACCOUNT_ID,
            'username' => self::USERNAME,
            'email' => self::EMAIL,
            'password_hash' => password_hash('OldPass123!', PASSWORD_BCRYPT),
            'full_name' => 'Race Teller',
            'role' => 'teller',
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $connection->table('password_reset_otps')->insert([
            'account_type' => 'staff',
            'account_id' => self::ACCOUNT_ID,
            'email' => self::EMAIL,
            'otp_hash' => password_hash(self::OTP, PASSWORD_BCRYPT),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(15)->toDateTimeString(),
            'consumed_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * @param  list<array{token: string, password: string}>  $jobs
     * @return list<array{token: string, ok: bool, status: int, field?: string, message?: string, error?: string}>
     */
    private function runWorkersConcurrently(array $jobs): array
    {
        $dir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'bank_mini_race_'.bin2hex(random_bytes(6));
        mkdir($dir);
        $barrier = $dir.DIRECTORY_SEPARATOR.'go';

        $connectionConfig = (string) json_encode(config('database.connections.'.$this->connectionName));
        $processes = [];
        $readyFiles = [];

        try {
            foreach ($jobs as $job) {
                $ready = $dir.DIRECTORY_SEPARATOR.'ready_'.$job['token'];
                $readyFiles[] = $ready;

                $processes[] = [
                    'token' => $job['token'],
                    'process' => $this->spawnWorker($barrier, $ready, $job, $connectionConfig),
                ];
            }

            $deadline = microtime(true) + 20.0;

            foreach ($readyFiles as $ready) {
                while (! is_file($ready)) {
                    if (microtime(true) > $deadline) {
                        $this->fail('worker processes did not reach the barrier in time');
                    }

                    usleep(2000);
                }
            }

            touch($barrier);

            $results = [];

            foreach ($processes as $entry) {
                $process = $entry['process'];
                $process->wait();

                $payload = json_decode($process->getOutput(), true);

                if (! is_array($payload)) {
                    $this->fail("worker {$entry['token']} emitted unreadable output: ".$process->getErrorOutput());
                }

                $payload['token'] = $entry['token'];
                $results[] = $payload;
            }

            return $results;
        } finally {
            foreach ($processes as $entry) {
                $entry['process']->stop(0);
            }

            @rmdir($dir);
        }
    }

    private function spawnWorker(string $barrier, string $ready, array $job, string $connectionConfig): Process
    {
        $process = new Process([
            PHP_BINARY,
            base_path('tests/Support/concurrent_reset_worker.php'),
            $job['token'],
            $barrier,
            $ready,
            'staff',
            self::USERNAME,
            self::OTP,
            $job['password'],
            $this->connectionName,
            $connectionConfig,
        ], base_path(), $this->workerEnvironment());

        $process->setTimeout(60);
        $process->start();

        return $process;
    }

    private function workerEnvironment(): array
    {
        $environment = array_merge([], getenv());
        $environment['APP_ENV'] = 'testing';
        $environment['APP_KEY'] = (string) config('app.key');
        $environment['APP_DEBUG'] = 'false';
        $environment['DB_CONNECTION'] = 'recovery_race';
        $environment['MAIL_MAILER'] = 'array';
        $environment['CACHE_STORE'] = 'array';
        $environment['SESSION_DRIVER'] = 'array';
        $environment['QUEUE_CONNECTION'] = 'sync';
        $environment['BCRYPT_ROUNDS'] = '4';
        unset($environment['DB_DATABASE'], $environment['RECOVERY_RACE_MYSQL']);

        return $environment;
    }

    /**
     * @param  list<array{token: string, ok: bool, status: int}>  $results
     */
    private function assertPasswordMatchesWorkerWinner(string $storedHash, array $results): void
    {
        $this->assertNotEmpty($storedHash);

        $matches = 0;

        foreach ($results as $result) {
            $password = $result['token'] === 'winner' ? self::WINNER_PASSWORD : self::LOSER_PASSWORD;

            if (password_verify($password, $storedHash)) {
                $matches++;
                $this->assertTrue($result['ok'], "password was applied by a worker that reported failure: {$result['token']}");
            }
        }

        $this->assertSame(1, $matches, 'the stored credential must match exactly one concurrent worker');
    }
}
