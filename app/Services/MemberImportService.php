<?php

namespace App\Services;

use App\Models\Member;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class MemberImportService
{
    private const CHUNK_SIZE = 100;

    private const MAX_REPORTED_ISSUES = 50;

    /**
     * @return array{imported: int, skipped: int, failed: int, issues: array<int, array{row: int|null, message: string}>}
     */
    public function importFromCsv(string $path, int $actorId): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw new RuntimeException('File tidak dapat dibaca.');
        }

        try {
            $delimiter = $this->detectDelimiter($handle);

            $headers = fgetcsv($handle, 0, $delimiter);

            if ($headers === false) {
                throw new InvalidArgumentException('File CSV kosong.');
            }

            $columns = $this->mapHeaders($headers);

            return $this->processRows($handle, $delimiter, $columns, $actorId);
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  resource  $handle
     */
    private function detectDelimiter($handle): string
    {
        $sample = (string) fread($handle, 2048);
        rewind($handle);

        if (substr_count($sample, ';') > substr_count($sample, ',')) {
            return ';';
        }

        return ',';
    }

    /**
     * @param  array<int, string|false|null>  $headers
     * @return array{nis: int, nama: int, angkatan_keluar: ?int}
     */
    private function mapHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $index => $header) {
            $normalized[$index] = mb_strtolower(trim(str_replace("\xEF\xBB\xBF", '', (string) $header)));
        }

        $columns = [
            'nis' => array_search('nis', $normalized, true),
            'nama' => array_search('nama', $normalized, true),
            'angkatan_keluar' => array_search('angkatan_keluar', $normalized, true),
        ];

        if ($columns['nis'] === false || $columns['nama'] === false) {
            throw new InvalidArgumentException('Header CSV harus memuat kolom "nis" dan "nama".');
        }

        return [
            'nis' => (int) $columns['nis'],
            'nama' => (int) $columns['nama'],
            'angkatan_keluar' => $columns['angkatan_keluar'] === false ? null : (int) $columns['angkatan_keluar'],
        ];
    }

    /**
     * @param  resource  $handle
     * @param  array{nis: int, nama: int, angkatan_keluar: ?int}  $columns
     * @return array{imported: int, skipped: int, failed: int, issues: array<int, array{row: int|null, message: string}>}
     */
    private function processRows($handle, string $delimiter, array $columns, int $actorId): array
    {
        $summary = ['imported' => 0, 'skipped' => 0, 'failed' => 0, 'issues' => []];
        $seenNis = [];
        $buffer = [];

        $rowNumber = 1;

        while (($raw = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;

            $nis = isset($raw[$columns['nis']]) ? trim((string) $raw[$columns['nis']]) : '';
            $nama = isset($raw[$columns['nama']]) ? trim((string) $raw[$columns['nama']]) : '';
            $angkatan = $columns['angkatan_keluar'] !== null && isset($raw[$columns['angkatan_keluar']])
                ? trim((string) $raw[$columns['angkatan_keluar']])
                : '';

            if ($nis === '' && $nama === '' && $angkatan === '') {
                continue;
            }

            $error = $this->validateRow($nis, $nama, $angkatan, $seenNis);

            if ($error !== null) {
                $summary['failed']++;
                $this->reportIssue($summary, $rowNumber, $error);

                continue;
            }

            $seenNis[$nis] = true;
            $buffer[] = compact('nis', 'nama', 'angkatan');

            if (count($buffer) >= self::CHUNK_SIZE) {
                $this->flushBuffer($buffer, $actorId, $summary);
                $buffer = [];
            }
        }

        $this->flushBuffer($buffer, $actorId, $summary);

        return $summary;
    }

    private function validateRow(string $nis, string $nama, string $angkatan, array $seenNis): ?string
    {
        if ($nis === '') {
            return 'Kolom nis wajib diisi.';
        }

        if (mb_strlen($nis) > 30) {
            return 'Kolom nis melebihi 30 karakter.';
        }

        if (isset($seenNis[$nis])) {
            return "NIS {$nis} duplikat di dalam file.";
        }

        if ($nama === '') {
            return 'Kolom nama wajib diisi.';
        }

        if (mb_strlen($nama) > 100) {
            return 'Kolom nama melebihi 100 karakter.';
        }

        if ($angkatan !== '' && mb_strlen($angkatan) > 10) {
            return 'Kolom angkatan_keluar melebihi 10 karakter.';
        }

        return null;
    }

    /**
     * @param  array<int, array{nis: string, nama: string, angkatan: string}>  $buffer
     * @param  array{imported: int, skipped: int, failed: int, issues: array<int, array{row: int|null, message: string}>}  $summary
     */
    private function flushBuffer(array &$buffer, int $actorId, array &$summary): void
    {
        if ($buffer === []) {
            return;
        }

        DB::transaction(function () use (&$buffer, $actorId, &$summary) {
            $existing = Member::query()
                ->whereIn('nis', array_column($buffer, 'nis'))
                ->pluck('nis')
                ->all();

            foreach ($buffer as $entry) {
                if (in_array($entry['nis'], $existing, true)) {
                    $summary['skipped']++;
                    $this->reportIssue($summary, null, "NIS {$entry['nis']} sudah terdaftar.");

                    continue;
                }

                Member::create([
                    'nis' => $entry['nis'],
                    'full_name' => $entry['nama'],
                    'graduation_year' => $entry['angkatan'] !== '' ? $entry['angkatan'] : null,
                    'status' => Member::STATUS_ACCOUNT_INACTIVE,
                    'created_by' => $actorId,
                ]);

                $summary['imported']++;
            }
        });

        $buffer = [];
    }

    /**
     * @param  array{imported: int, skipped: int, failed: int, issues: array<int, array{row: int|null, message: string}>}  $summary
     */
    private function reportIssue(array &$summary, ?int $row, string $message): void
    {
        if (count($summary['issues']) < self::MAX_REPORTED_ISSUES) {
            $summary['issues'][] = [
                'row' => $row,
                'message' => $message,
            ];
        }
    }
}
