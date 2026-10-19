<?php

namespace App\Support;

use App\Models\Member;
use App\Models\Saver;
use App\Models\SystemConfig;

/**
 * Aturan penamaan kelas: "<tingkat> <jurusan> <rombel>", misalnya "XI RPL 2".
 *
 * Alasan: kelas penabung dulu hanyalah teks bebas yang diketik ulang di setiap form,
 * sehingga rawan salah ketik dan menyulitkan kenaikan kelas. Form kini mengambil
 * pilihan dari sini supaya kelas yang tersimpan seragam, tanpa memaksa data lama
 * (misalnya "VII A") ikut berubah.
 */
class ClassRoom
{
    public const LEVELS = ['X', 'XI', 'XII'];

    public const DEFAULT_MAJORS = ['RPL', 'TKJ', 'DKV', 'AK', 'LPS', 'MP', 'BR', 'BD'];

    /**
     * Jumlah rombel yang disarankan pada daftar isian kelas. Hanya saran: jumlah rombel
     * tiap jurusan berbeda dan berubah tiap tahun, jadi tidak ada batas yang dipaksakan.
     */
    private const SUGGESTED_ROMBELS = 12;

    /**
     * Daftar jurusan resmi dari Konfigurasi, dipakai dropdown tingkat pencarian kelas.
     *
     * @return list<string>
     */
    public static function majors(): array
    {
        $configured = (string) SystemConfig::value('class_majors', implode(',', self::DEFAULT_MAJORS));

        $majors = [];

        foreach (explode(',', $configured) as $item) {
            $item = mb_strtoupper(trim($item));

            if ($item === '' || preg_match('/^[A-Z]{1,6}$/', $item) !== 1) {
                continue;
            }

            if (! in_array($item, $majors, true)) {
                $majors[] = $item;
            }
        }

        return $majors === [] ? self::DEFAULT_MAJORS : $majors;
    }

    /**
     * Pilihan jurusan untuk dropdown: daftar resmi ditambah jurusan yang benar-benar
     * ada di data, supaya kelas lama (misalnya jurusan yang sudah ditutup) tetap bisa dipetakan.
     *
     * @return list<string>
     */
    public static function optionMajors(): array
    {
        $majors = self::majors();

        foreach (self::existingClasses() as $class) {
            $major = self::parse($class)['major'];

            if ($major !== null && ! in_array($major, $majors, true)) {
                $majors[] = $major;
            }
        }

        return $majors;
    }

    /**
     * Kelas yang benar-benar terpakai, untuk saran isian (datalist) dan daftar filter.
     *
     * @return list<string>
     */
    public static function existingClasses(): array
    {
        return Saver::query()
            ->whereNotNull('class_name')
            ->distinct()
            ->pluck('class_name')
            ->merge(Member::query()->whereNotNull('class_name')->distinct()->pluck('class_name'))
            ->map(static fn ($value): string => trim((string) $value))
            ->filter(static fn (string $value): bool => $value !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * Saran isian kelas: kelas yang sudah ada lebih dulu, lalu kombinasi tingkat x jurusan
     * x nomor rombel. Datalist hanya membantu mengetik, bukan memaksa format.
     *
     * @return list<string>
     */
    public static function suggestions(): array
    {
        $suggestions = self::existingClasses();

        foreach (self::LEVELS as $level) {
            foreach (self::optionMajors() as $major) {
                for ($rombel = 1; $rombel <= self::SUGGESTED_ROMBELS; $rombel++) {
                    $suggestions[] = self::compose($level, $major, (string) $rombel);
                }
            }
        }

        return array_values(array_unique($suggestions));
    }

    public static function compose(string $level, string $major, int|string|null $rombel = null): string
    {
        $parts = [mb_strtoupper(trim($level)), mb_strtoupper(trim($major))];
        $rombel = trim((string) $rombel);

        if ($rombel !== '') {
            $parts[] = $rombel;
        }

        return implode(' ', array_filter($parts, static fn (string $part): bool => $part !== ''));
    }

    /**
     * Urai nama kelas menjadi tingkat, jurusan, dan nomor rombel. Toleran terhadap huruf
     * kecil dan spasi berlebih; tingkat hanya dikenali dari daftar baku.
     *
     * @return array{level: string|null, major: string|null, rombel: string|null}
     */
    public static function parse(?string $class): array
    {
        $tokens = preg_split('/\s+/', mb_strtoupper(trim((string) $class)), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $level = null;
        $rombel = null;

        if ($tokens !== [] && in_array($tokens[0], self::LEVELS, true)) {
            $level = array_shift($tokens);
        }

        if ($tokens !== [] && preg_match('/^\d{1,2}$/', $tokens[count($tokens) - 1]) === 1) {
            $rombel = array_pop($tokens);
        }

        return [
            'level' => $level,
            'major' => $tokens === [] ? null : implode(' ', $tokens),
            'rombel' => $rombel,
        ];
    }

    public static function nextLevel(?string $level): ?string
    {
        $index = array_search(mb_strtoupper(trim((string) $level)), self::LEVELS, true);

        if ($index === false) {
            return null;
        }

        return self::LEVELS[$index + 1] ?? null;
    }

    /**
     * Kelas setelah naik tingkat. Hanya tingkatnya yang berubah; jurusan dan nomor rombel
     * dibiarkan supaya jumlah rombel tetap seperti tahun sebelumnya. Kelas tingkat akhir
     * dan kelas yang tingkatnya tidak dikenali mengembalikan null (dipetakan manual).
     */
    public static function promote(?string $class): ?string
    {
        $parts = self::parse($class);
        $next = self::nextLevel($parts['level']);

        if ($parts['level'] === null || $parts['major'] === null || $next === null) {
            return null;
        }

        return self::compose($next, $parts['major'], $parts['rombel']);
    }
}
