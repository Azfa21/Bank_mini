<?php

namespace Tests\Feature\Admin;

use App\Models\Member;
use Illuminate\Http\UploadedFile;
use Tests\Feature\Teller\TellerTestCase;

class MemberImportTest extends TellerTestCase
{
    /** @var list<string> */
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    private function csv(string $content): UploadedFile
    {
        $path = sys_get_temp_dir().'/member-import-test-'.uniqid('', true).'.csv';
        file_put_contents($path, "\xEF\xBB\xBF".$content);
        $this->tempFiles[] = $path;

        return new UploadedFile($path, 'anggota.csv', 'text/csv', null, true);
    }

    public function test_imports_valid_rows_as_inactive_members(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('admin.members.import'), [
                'file' => $this->csv(
                    "nis,nama,angkatan_keluar\n"
                    ."20260001,Budi Santoso,2026\n"
                    ."20260002,Citra Lestari,\n"
                    ."20260003,Dewi Anggraini,2027\n"
                ),
            ])
            ->assertRedirect(route('admin.members.import.form'))
            ->assertSessionHas('import_summary', function (array $summary): bool {
                $this->assertSame(3, $summary['imported']);
                $this->assertSame(0, $summary['skipped']);
                $this->assertSame(0, $summary['failed']);
                $this->assertSame([], $summary['issues']);

                return true;
            });

        $member = Member::where('nis', '20260001')->firstOrFail();
        $this->assertSame('Budi Santoso', $member->full_name);
        $this->assertSame('2026', $member->graduation_year);
        $this->assertSame(Member::STATUS_ACCOUNT_INACTIVE, $member->status);
        $this->assertSame($this->admin->id, $member->created_by);
        $this->assertDatabaseCount('accounts', 1);
    }

    public function test_skips_duplicates_in_database_and_within_file(): void
    {
        Member::create([
            'nis' => '20260001',
            'full_name' => 'Sudah Terdaftar',
            'status' => Member::STATUS_ACCOUNT_INACTIVE,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.members.import'), [
                'file' => $this->csv(
                    "nis,nama,angkatan_keluar\n"
                    ."20260001,Duplikat Database,2026\n"
                    ."20260002,Baru Pertama,\n"
                    ."20260003,Baru Kedua,\n"
                    ."20260003,Duplikat File,\n"
                ),
            ])
            ->assertRedirect(route('admin.members.import.form'))
            ->assertSessionHas('import_summary', function (array $summary): bool {
                $this->assertSame(2, $summary['imported']);
                $this->assertSame(1, $summary['skipped']);
                $this->assertSame(1, $summary['failed']);

                return true;
            });

        $this->assertDatabaseHas('members', ['nis' => '20260002']);
        $this->assertDatabaseMissing('members', ['full_name' => 'Duplikat File']);
        $this->assertSame('Sudah Terdaftar', Member::where('nis', '20260001')->value('full_name'));
    }

    public function test_reports_invalid_rows_without_importing_them(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('admin.members.import'), [
                'file' => $this->csv(
                    "nis,nama,angkatan_keluar\n"
                    .",Nama Tanpa Nis,\n"
                    ."20260001,,\n"
                ),
            ])
            ->assertRedirect(route('admin.members.import.form'))
            ->assertSessionHas('import_summary', function (array $summary): bool {
                $this->assertSame(0, $summary['imported']);
                $this->assertSame(2, $summary['failed']);
                $this->assertCount(2, $summary['issues']);
                $this->assertSame('Kolom nis wajib diisi.', $summary['issues'][0]['message']);
                $this->assertSame('Kolom nama wajib diisi.', $summary['issues'][1]['message']);

                return true;
            });

        $this->assertDatabaseCount('members', 0);
    }

    public function test_supports_semicolon_delimiter(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('admin.members.import'), [
                'file' => $this->csv(
                    "nis;nama;angkatan_keluar\n"
                    ."20260011;Semi Kolon;2028\n"
                ),
            ])
            ->assertRedirect(route('admin.members.import.form'))
            ->assertSessionHas('import_summary', function (array $summary): bool {
                $this->assertSame(1, $summary['imported']);

                return true;
            });

        $member = Member::where('nis', '20260011')->firstOrFail();
        $this->assertSame('Semi Kolon', $member->full_name);
        $this->assertSame('2028', $member->graduation_year);
    }

    public function test_rejects_file_with_invalid_headers(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('admin.members.import'), [
                'file' => $this->csv("nomor_induk,nama_lengkap\n20260001,Budi\n"),
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Header CSV harus memuat kolom "nis" dan "nama".');

        $this->assertDatabaseCount('members', 0);
    }

    public function test_rejects_non_csv_file(): void
    {
        $path = sys_get_temp_dir().'/member-import-test-'.uniqid('', true).'.pdf';
        file_put_contents($path, '%PDF-fake');
        $this->tempFiles[] = $path;

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.members.import'), [
                'file' => new UploadedFile($path, 'anggota.pdf', 'application/pdf', null, true),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_template_download_returns_csv_with_expected_columns(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->get(route('admin.members.import.template'));

        $response->assertOk();

        $content = preg_replace('/^\xEF\xBB\xBF/', '', $response->streamedContent());
        $lines = explode("\n", trim((string) $content));

        $this->assertSame(['nis', 'nama', 'angkatan_keluar', 'kelas'], str_getcsv($lines[0]));
        $this->assertSame(['20260001', 'Budi Santoso', '2029', 'X RPL 1'], str_getcsv($lines[1]));
    }

    public function test_imports_class_column_and_updates_existing_member_class(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('admin.members.import'), [
                'file' => $this->csv(
                    "nis,nama,angkatan_keluar,kelas\n"
                    ."20260030,Baru Punya Kelas,2029,X RPL 1\n"
                ),
            ])
            ->assertRedirect(route('admin.members.import.form'));

        $this->assertSame('X RPL 1', Member::where('nis', '20260030')->value('class_name'));

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.members.import'), [
                'file' => $this->csv(
                    "nis,nama,angkatan_keluar,kelas\n"
                    ."20260030,Baru Punya Kelas,2029,XI RPL 2\n"
                ),
            ])
            ->assertRedirect(route('admin.members.import.form'))
            ->assertSessionHas('import_summary', function (array $summary): bool {
                $this->assertSame(0, $summary['imported']);
                $this->assertSame(1, $summary['updated']);
                $this->assertSame(0, $summary['skipped']);

                return true;
            });

        $this->assertSame('XI RPL 2', Member::where('nis', '20260030')->value('class_name'));
    }

    public function test_import_without_class_column_keeps_existing_member_class(): void
    {
        Member::create([
            'nis' => '20260040',
            'full_name' => 'Sudah Ada',
            'class_name' => 'X TKJ 1',
            'status' => Member::STATUS_ACCOUNT_INACTIVE,
        ]);

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.members.import'), [
                'file' => $this->csv(
                    "nis,nama,angkatan_keluar\n"
                    ."20260040,Sudah Ada,2029\n"
                ),
            ])
            ->assertSessionHas('import_summary', function (array $summary): bool {
                $this->assertSame(0, $summary['updated']);
                $this->assertSame(1, $summary['skipped']);

                return true;
            });

        $this->assertSame('X TKJ 1', Member::where('nis', '20260040')->value('class_name'));
    }

    public function test_rejects_row_with_class_longer_than_30_characters(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('admin.members.import'), [
                'file' => $this->csv(
                    "nis,nama,angkatan_keluar,kelas\n"
                    .'20260050,Nama Kelas Panjang,2029,'.str_repeat('X', 31)."\n"
                ),
            ])
            ->assertSessionHas('import_summary', function (array $summary): bool {
                $this->assertSame(1, $summary['failed']);
                $this->assertSame('Kolom kelas melebihi 30 karakter.', $summary['issues'][0]['message']);

                return true;
            });

        $this->assertDatabaseMissing('members', ['nis' => '20260050']);
    }

    public function test_non_admin_cannot_import(): void
    {
        $this->actingAsTeller()
            ->postJson(route('admin.members.import'), [])
            ->assertForbidden();
    }
}
