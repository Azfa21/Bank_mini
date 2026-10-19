<?php

namespace App\Services;

use App\Models\Saver;
use App\Models\SystemConfig;
use App\Models\User;
use App\Support\ClassRoom;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClassPromotionService
{
    public const LAST_RUN_KEY = 'class_promotion_last_run';

    /**
     * Penabung yang masih terdaftar, dikelompokkan per kelas. Alumni tidak ikut: kelas
     * mereka tidak lagi dipakai dan tidak boleh berubah. Urutan baris disusun di PHP karena
     * "kind" ditentukan dari isi nama kelas, bukan dari kolom basis data.
     *
     * @return list<array{class: string, total: int, blocked: int, level: string|null, major: string|null, rombel: string|null, next_level: string|null, kind: string}>
     */
    public function classGroups(): array
    {
        $statuses = [Saver::STATUS_ACTIVE, Saver::STATUS_BLOCKED];

        $totals = Saver::query()
            ->whereIn('status', $statuses)
            ->groupBy('class_name')
            ->selectRaw('class_name, count(*) as total')
            ->pluck('total', 'class_name');

        $blocked = Saver::query()
            ->where('status', Saver::STATUS_BLOCKED)
            ->groupBy('class_name')
            ->selectRaw('class_name, count(*) as total')
            ->pluck('total', 'class_name');

        $groups = [];

        foreach ($totals as $class => $total) {
            $class = trim((string) $class);
            $parts = ClassRoom::parse($class);
            $next = ClassRoom::nextLevel($parts['level']);

            $groups[] = [
                'class' => $class,
                'total' => (int) $total,
                'blocked' => (int) ($blocked[$class] ?? 0),
                'level' => $parts['level'],
                'major' => $parts['major'],
                'rombel' => $parts['rombel'],
                'next_level' => $next,
                'kind' => match (true) {
                    $class === '' => 'empty',
                    $parts['level'] === null => 'unknown',
                    $next === null => 'final',
                    default => 'ready',
                },
            ];
        }

        // Kelas yang belum diisi diletakkan paling bawah: bukan baris kerja utama.
        $sorted = collect($groups)
            ->sortBy(static fn (array $group): string => ($group['class'] === '' ? '1' : '0').mb_strtoupper($group['class']))
            ->all();

        return array_values($sorted);
    }

    /**
     * Penabung aktif/diblokir yang kelasnya masih kosong. Ditampilkan di halaman kenaikan
     * kelas supaya tidak ada siswa yang diam-diam tertinggal.
     *
     * @return Collection<int, Saver>
     */
    public function withoutClass(int $limit = 20): Collection
    {
        return Saver::query()
            ->whereIn('status', [Saver::STATUS_ACTIVE, Saver::STATUS_BLOCKED])
            ->where(function ($query) {
                $query->whereNull('class_name')->orWhere('class_name', '');
            })
            ->orderBy('full_name')
            ->limit($limit)
            ->get();
    }

    /**
     * Kelas tingkat akhir yang belum lulus dan karenanya tidak ikut dinaikkan.
     *
     * @param  array<int, array{class: string, total: int, blocked: int, level: string|null, major: string|null, rombel: string|null, next_level: string|null, kind: string}>  $groups
     */
    public function finalYearTotal(array $groups): int
    {
        return (int) collect($groups)
            ->filter(static fn (array $group): bool => $group['level'] === 'XII')
            ->sum('total');
    }

    /**
     * Terapkan kenaikan kelas. Semua penabung dibaca lebih dulu sebelum ada perubahan,
     * jadi satu siswa tidak mungkin naik dua kali dalam satu kali proses (misalnya kelas
     * "X RPL 1" menjadi "XI RPL 1" sementara baris berikutnya memindahkan "XI RPL 1").
     *
     * @param  array<int, array{class?: string|null, level?: string|null, major?: string|null, rombel?: string|null}>  $rows
     * @return array{classes: int, savers: int, skipped: int, mapping: array<string, string>}
     */
    public function apply(array $rows, string $academicYear, ?User $admin = null): array
    {
        return DB::transaction(function () use ($rows, $academicYear, $admin) {
            $plan = [];
            $mapping = [];
            $skipped = 0;

            foreach ($rows as $row) {
                $source = trim((string) ($row['class'] ?? ''));
                $level = mb_strtoupper(trim((string) ($row['level'] ?? '')));
                $major = mb_strtoupper(trim((string) ($row['major'] ?? '')));

                if ($source === '' || $major === '' || ! in_array($level, ClassRoom::LEVELS, true)) {
                    $skipped++;

                    continue;
                }

                $target = ClassRoom::compose($level, $major, $row['rombel'] ?? null);

                if ($target === $source || mb_strlen($target) > 30) {
                    $skipped++;

                    continue;
                }

                $ids = Saver::query()
                    ->whereIn('status', [Saver::STATUS_ACTIVE, Saver::STATUS_BLOCKED])
                    ->where('class_name', $source)
                    ->pluck('id')
                    ->all();

                if ($ids === []) {
                    $skipped++;

                    continue;
                }

                $plan[$target] = array_merge($plan[$target] ?? [], $ids);
                $mapping[$source] = $target;
            }

            $savers = 0;

            foreach ($plan as $target => $ids) {
                Saver::query()->whereKey($ids)->update(['class_name' => $target]);
                $savers += count($ids);
            }

            AuditLogService::record('class_promotion_processed', 'saver', null, [
                'academic_year' => $academicYear,
                'mapping' => $mapping,
                'savers' => $savers,
                'skipped_rows' => $skipped,
            ], $admin?->id);

            SystemConfig::updateOrCreate(
                ['config_key' => self::LAST_RUN_KEY],
                ['config_value' => $academicYear],
            );

            return ['classes' => count($plan), 'savers' => $savers, 'skipped' => $skipped, 'mapping' => $mapping];
        });
    }

    /**
     * Pindahkan sebagian penabung ke rombel lain. Dipakai saat jumlah rombel berubah
     * (mis. dua kelas dibagi menjadi tiga) atau ada siswa pindah rombel di tengah tahun.
     *
     * @param  array<int, int>  $saverIds
     * @return array{moved: int, requested: int}
     */
    public function move(array $saverIds, string $target, ?User $admin = null): array
    {
        return DB::transaction(function () use ($saverIds, $target, $admin) {
            $savers = Saver::query()
                ->whereKey($saverIds)
                ->whereIn('status', [Saver::STATUS_ACTIVE, Saver::STATUS_BLOCKED])
                ->get(['id', 'class_name']);

            $moved = $savers->filter(static fn (Saver $saver): bool => trim((string) $saver->class_name) !== $target);

            if ($moved->isNotEmpty()) {
                Saver::query()->whereKey($moved->modelKeys())->update(['class_name' => $target]);
            }

            AuditLogService::record('class_members_moved', 'saver', null, [
                'target' => $target,
                'savers' => $moved->count(),
                'requested' => count($saverIds),
            ], $admin?->id);

            return ['moved' => $moved->count(), 'requested' => count($saverIds)];
        });
    }

    /**
     * Tahun ajaran kenaikan kelas terakhir, dipakai sebagai pengaman agar tombol yang
     * sama tidak ditekan dua kali pada tahun ajaran yang sama.
     */
    public function lastRun(): ?string
    {
        $value = SystemConfig::value(self::LAST_RUN_KEY);

        return $value === null || $value === '' ? null : (string) $value;
    }
}
