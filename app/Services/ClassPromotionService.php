<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Saver;
use App\Models\SystemConfig;
use App\Models\User;
use App\Support\ClassRoom;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClassPromotionService
{
    public const LAST_RUN_KEY = 'class_promotion_last_run';

    public const ACTION_PROMOTE = 'promote';

    public const ACTION_RETAIN = 'retain';

    /**
     * Anggota yang belum punya rekening dan penabung yang masih aktif, dikelompokkan per kelas.
     * Anggota dengan penabung tidak dihitung karena kelasnya terbaca dari sisi penabung. Alumni
     * dikeluarkan: kelas mereka tidak lagi dipakai dan tidak boleh berubah.
     *
     * @return list<array{class: string, members: int, savers: int, blocked: int, level: string|null, target: string|null, kind: string}>
     */
    public function classGroups(): array
    {
        $memberTotals = $this->totals(Member::query()
            ->whereNull('graduated_at')
            ->whereNotIn('nis', Saver::withTrashed()->whereNotNull('member_nis')->select('member_nis'))
            ->groupBy('class_name')
            ->selectRaw('class_name, count(*) as total')
            ->pluck('total', 'class_name'));

        $saverTotals = $this->totals(Saver::query()
            ->whereIn('status', [Saver::STATUS_ACTIVE, Saver::STATUS_BLOCKED])
            ->groupBy('class_name')
            ->selectRaw('class_name, count(*) as total')
            ->pluck('total', 'class_name'));

        $blocked = $this->totals(Saver::query()
            ->where('status', Saver::STATUS_BLOCKED)
            ->groupBy('class_name')
            ->selectRaw('class_name, count(*) as total')
            ->pluck('total', 'class_name'));

        $classes = array_unique(array_merge(array_keys($memberTotals), array_keys($saverTotals)));
        sort($classes);

        $groups = [];

        foreach ($classes as $class) {
            $parts = ClassRoom::parse($class);
            $target = ClassRoom::promote($class);

            $groups[] = [
                'class' => $class,
                'members' => $memberTotals[$class] ?? 0,
                'savers' => $saverTotals[$class] ?? 0,
                'blocked' => $blocked[$class] ?? 0,
                'level' => $parts['level'],
                'target' => $target,
                'kind' => match (true) {
                    $parts['level'] === 'XII' => 'final',
                    $target === null => 'unknown',
                    default => 'ready',
                },
            ];
        }

        return $groups;
    }

    /**
     * Daftar siswa tiap kelas yang siap dinaikkan, dipakai untuk memilih satu per satu
     * siapa yang tinggal kelas. Satu siswa yang sudah punya rekening hanya muncul sekali:
     * anggota yang punya rekening dikeluarkan dari daftar anggota dan terbaca dari penabungnya.
     *
     * @param  list<array{class: string, kind: string}>  $groups
     * @return array<string, list<array{key: string, name: string, number: string, type: string, blocked: bool}>>
     */
    public function studentsByClass(array $groups): array
    {
        $ready = [];

        foreach ($groups as $group) {
            if ($group['kind'] === 'ready') {
                $ready[trim($group['class'])] = true;
            }
        }

        if ($ready === []) {
            return [];
        }

        $students = [];

        $savers = Saver::query()
            ->whereIn('status', [Saver::STATUS_ACTIVE, Saver::STATUS_BLOCKED])
            ->orderBy('full_name')
            ->get(['student_number', 'full_name', 'class_name', 'status']);

        foreach ($savers as $saver) {
            $class = trim((string) $saver->class_name);

            if (! isset($ready[$class])) {
                continue;
            }

            $students[$class][] = [
                'key' => 'saver:'.$saver->student_number,
                'name' => $saver->full_name,
                'number' => $saver->student_number,
                'type' => 'Penabung',
                'blocked' => $saver->status === Saver::STATUS_BLOCKED,
            ];
        }

        $members = Member::query()
            ->whereNull('graduated_at')
            ->whereNotIn('nis', Saver::withTrashed()->whereNotNull('member_nis')->select('member_nis'))
            ->orderBy('full_name')
            ->get(['nis', 'full_name', 'class_name']);

        foreach ($members as $member) {
            $class = trim((string) $member->class_name);

            if (! isset($ready[$class])) {
                continue;
            }

            $students[$class][] = [
                'key' => 'member:'.$member->nis,
                'name' => $member->full_name,
                'number' => $member->nis,
                'type' => 'Anggota',
                'blocked' => false,
            ];
        }

        foreach ($students as $class => $list) {
            usort($list, static fn (array $a, array $b): int => mb_strtolower($a['name']) <=> mb_strtolower($b['name']));
            $students[$class] = $list;
        }

        return $students;
    }

    /**
     * Anggota dan penabung yang kelasnya masih kosong. Ditampilkan di halaman kenaikan
     * kelas supaya tidak ada siswa yang diam-diam tertinggal.
     *
     * @return array{members: Collection<int, Member>, savers: Collection<int, Saver>}
     */
    public function withoutClass(int $limit = 20): array
    {
        $members = Member::query()
            ->whereNull('graduated_at')
            ->whereNotIn('nis', Saver::withTrashed()->whereNotNull('member_nis')->select('member_nis'))
            ->where(function ($query) {
                $query->whereNull('class_name')->orWhere('class_name', '');
            })
            ->orderBy('full_name')
            ->limit($limit)
            ->get();

        $savers = Saver::query()
            ->whereIn('status', [Saver::STATUS_ACTIVE, Saver::STATUS_BLOCKED])
            ->where(function ($query) {
                $query->whereNull('class_name')->orWhere('class_name', '');
            })
            ->orderBy('full_name')
            ->limit($limit)
            ->get();

        return ['members' => $members, 'savers' => $savers];
    }

    /**
     * @param  array<int, array{class: string, members: int, savers: int, blocked: int, level: string|null, target: string|null, kind: string}>  $groups
     * @return array{members: int, savers: int}
     */
    public function finalYearCounts(array $groups): array
    {
        $final = collect($groups)->where('level', 'XII');

        return [
            'members' => (int) $final->sum('members'),
            'savers' => (int) $final->sum('savers'),
        ];
    }

    /**
     * Terapkan kenaikan kelas sekaligus tinggal kelas. Semua sasaran dibaca lebih dulu
     * sebelum ada perubahan, jadi satu siswa tidak mungkin naik dua kali dalam satu proses
     * (misalnya "X RPL 1" menjadi "XI RPL 1" sementara baris berikutnya memproses "XI RPL 1").
     *
     * Tindakan tinggal kelas ada dua tingkat: seluruh kelas lewat action retain, atau
     * siswa tertentu lewat retain[] pada baris yang dinaikkan. Siswa yang dicentang tidak
     * disentuh sama sekali, jadi kelasnya tetap dan rekan seangkatannya tetap naik.
     *
     * @param  array<int, array{class?: string|null, action?: string|null, retain?: array<int|string, mixed>|null}>  $rows
     * @return array{classes: int, members: int, savers: int, retained: int, retained_students: int, skipped: int, mapping: array<string, string>}
     */
    public function apply(array $rows, string $academicYear, ?User $admin = null): array
    {
        return DB::transaction(function () use ($rows, $academicYear, $admin) {
            $memberPlan = [];
            $saverPlan = [];
            $mapping = [];
            $retainedByClass = [];
            $retained = 0;
            $retainedStudents = 0;
            $skipped = 0;
            $handled = [];

            foreach ($rows as $row) {
                $source = trim((string) ($row['class'] ?? ''));

                if ($source === '' || isset($handled[$source])) {
                    continue;
                }

                $handled[$source] = true;

                if (($row['action'] ?? self::ACTION_PROMOTE) === self::ACTION_RETAIN) {
                    $retained++;

                    continue;
                }

                $target = ClassRoom::promote($source);

                if ($target === null || mb_strlen($target) > 30) {
                    $skipped++;

                    continue;
                }

                $keep = $this->retainedKeys($row['retain'] ?? []);
                $moveMembers = [];
                $staying = [];

                foreach ($this->promotableMembers($source) as $nis) {
                    if (isset($keep['members'][$nis])) {
                        $staying[] = 'member:'.$nis;
                    } else {
                        $moveMembers[] = $nis;
                    }
                }

                $moveSaverIds = [];

                foreach ($this->promotableSavers($source) as $saver) {
                    if (isset($keep['savers'][$saver['student_number']])) {
                        $staying[] = 'saver:'.$saver['student_number'];
                    } else {
                        $moveSaverIds[] = $saver['id'];
                    }
                }

                if ($staying !== []) {
                    $retainedStudents += count($staying);
                    $retainedByClass[$source] = $staying;
                }

                if ($moveMembers === [] && $moveSaverIds === []) {
                    // Seluruh siswa dicentang tinggal kelas: tidak ada yang bergerak, tapi ini
                    // bukan baris yang datanya hilang, jadi tidak dihitung sebagai baris dilewati.
                    if ($staying === []) {
                        $skipped++;
                    }

                    continue;
                }

                $memberPlan[$target] = array_merge($memberPlan[$target] ?? [], $moveMembers);
                $saverPlan[$target] = array_merge($saverPlan[$target] ?? [], $moveSaverIds);
                $mapping[$source] = $target;
            }

            $members = 0;

            foreach ($memberPlan as $target => $nis) {
                $members += Member::query()->whereIn('nis', $nis)->update(['class_name' => $target]);
            }

            $savers = 0;
            $linkedMembers = 0;

            foreach ($saverPlan as $target => $ids) {
                $linkedNis = Saver::query()->whereKey($ids)->whereNotNull('member_nis')->pluck('member_nis')->all();

                $savers += Saver::query()->whereKey($ids)->update(['class_name' => $target]);

                // Anggota yang sudah punya rekening menyimpan salinan kelas, jadi keduanya
                // disamakan supaya data kelulusan dan laporan tidak terpecah.
                if ($linkedNis !== []) {
                    $linkedMembers += Member::query()->whereIn('nis', $linkedNis)->update(['class_name' => $target]);
                }
            }

            AuditLogService::record('class_promotion_processed', 'saver', null, [
                'academic_year' => $academicYear,
                'mapping' => $mapping,
                'members' => $members,
                'savers' => $savers,
                'linked_members_synced' => $linkedMembers,
                'retained_classes' => $retained,
                'retained_students' => $retainedStudents,
                'retained_student_keys' => $retainedByClass,
                'skipped_rows' => $skipped,
            ], $admin?->id);

            SystemConfig::updateOrCreate(
                ['config_key' => self::LAST_RUN_KEY],
                ['config_value' => $academicYear],
            );

            return [
                'classes' => count($mapping),
                'members' => $members,
                'savers' => $savers,
                'retained' => $retained,
                'retained_students' => $retainedStudents,
                'skipped' => $skipped,
                'mapping' => $mapping,
            ];
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

    /**
     * @return list<string>
     */
    private function promotableMembers(string $class): array
    {
        return Member::query()
            ->whereNull('graduated_at')
            ->whereNotIn('nis', Saver::withTrashed()->whereNotNull('member_nis')->select('member_nis'))
            // TRIM supaya kelas yang tersimpan berspasi di tepi tetap ikut terbaca, sama
            // dengan perhitungan jumlah kelas di halaman ini.
            ->whereRaw('TRIM(class_name) = ?', [$class])
            ->pluck('nis')
            ->all();
    }

    /**
     * @return list<array{id: int, student_number: string}>
     */
    private function promotableSavers(string $class): array
    {
        return Saver::query()
            ->whereIn('status', [Saver::STATUS_ACTIVE, Saver::STATUS_BLOCKED])
            ->whereRaw('TRIM(class_name) = ?', [$class])
            ->get(['id', 'student_number'])
            ->map(static fn (Saver $saver): array => ['id' => (int) $saver->id, 'student_number' => (string) $saver->student_number])
            ->all();
    }

    /**
     * Kunci siswa yang dicentang tinggal kelas, dipisah per jenis pemilik datanya.
     * Kuncinya memakai nomor siswa, bukan id baris, supaya bisa dibaca langsung dari
     * catatan audit tanpa membuka tabel.
     *
     * @param  array<int|string, mixed>  $keys
     * @return array{members: array<string, true>, savers: array<string, true>}
     */
    private function retainedKeys(array $keys): array
    {
        $members = [];
        $savers = [];

        foreach ($keys as $key) {
            if (! is_string($key)) {
                continue;
            }

            if (str_starts_with($key, 'member:')) {
                $members[substr($key, 7)] = true;
            } elseif (str_starts_with($key, 'saver:')) {
                $savers[substr($key, 6)] = true;
            }
        }

        return ['members' => $members, 'savers' => $savers];
    }

    /**
     * Total per kelas dengan kunci bersih: nama kelas lama masih ada yang berspasi di tepi.
     *
     * @param  Collection<string, int|string>  $totals
     * @return array<string, int>
     */
    private function totals(Collection $totals): array
    {
        $clean = [];

        foreach ($totals as $class => $total) {
            $key = trim((string) $class);

            if ($key === '') {
                continue;
            }

            $clean[$key] = ($clean[$key] ?? 0) + (int) $total;
        }

        return $clean;
    }
}
