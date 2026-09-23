<?php

namespace Tests\Feature\Admin;

use App\Models\Account;
use App\Models\Customer;
use Illuminate\Http\UploadedFile;
use Tests\Feature\Teller\TellerTestCase;

class CustomerImportTest extends TellerTestCase
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
        $path = sys_get_temp_dir().'/import-test-'.uniqid('', true).'.csv';
        file_put_contents($path, "\xEF\xBB\xBF".$content);
        $this->tempFiles[] = $path;

        return new UploadedFile($path, 'siswa.csv', 'text/csv', null, true);
    }

    private function compactAcademicYear(): string
    {
        return now()->format('y').((int) now()->format('y') + 1);
    }

    public function test_imports_valid_rows_and_opens_sequential_accounts(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->post(route('admin.customers.import'), [
                'file' => $this->csv(
                    "nis,nama,kelas,no_hp,email\n"
                    ."20260001,Budi Santoso,VII A,081234567001,budi@example.test\n"
                    ."20260002,Citra Lestari,VII B,,citra@example.test\n"
                    ."20260003,Dewi Anggraini,VII C,,dewi@example.test\n"
                ),
            ])
            ->assertRedirect(route('admin.customers.import.form'));

        $response->assertSessionHas('import_summary', function (array $summary): bool {
            $this->assertSame(3, $summary['imported']);
            $this->assertSame(0, $summary['skipped']);
            $this->assertSame(0, $summary['failed']);
            $this->assertSame([], $summary['issues']);

            return true;
        });

        foreach (['20260001', '20260002', '20260003'] as $index => $nis) {
            $customer = Customer::where('student_number', $nis)->firstOrFail();
            $account = Account::where('customer_id', $customer->id)->firstOrFail();

            $this->assertSame($this->admin->id, $customer->created_by);
            $this->assertSame('active', $customer->status);

            $ordinal = str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT);
            $this->assertSame('10'.$this->compactAcademicYear().$ordinal, $account->account_number);
            $this->assertSame(now()->format('Y').'/'.(now()->format('Y') + 1), $account->academic_year);
            $this->assertSame(10000, $account->minimum_balance);
        }
    }

    public function test_skips_duplicates_in_database_and_within_file(): void
    {
        Customer::create([
            'student_number' => '20260001',
            'full_name' => 'Sudah Terdaftar',
            'pin_hash' => '123456',
            'qr_code_value' => 'qr-existing',
            'created_by' => $this->admin->id,
            'status' => 'active',
        ]);

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.customers.import'), [
                'file' => $this->csv(
                    "nis,nama,kelas,no_hp,email\n"
                    ."20260001,Duplikat Database,VII A,,duplikat.db@example.test\n"
                    ."20260002,Baru Pertama,VII B,,baru.pertama@example.test\n"
                    ."20260003,Baru Kedua,VII C,,baru.kedua@example.test\n"
                    ."20260003,Duplikat File,VII D,,duplikat.file@example.test\n"
                ),
            ])
            ->assertRedirect(route('admin.customers.import.form'))
            ->assertSessionHas('import_summary', function (array $summary): bool {
                $this->assertSame(2, $summary['imported']);
                $this->assertSame(1, $summary['skipped']);
                $this->assertSame(1, $summary['failed']);

                return true;
            });

        $this->assertDatabaseHas('customers', ['student_number' => '20260002']);
        $this->assertDatabaseMissing('customers', ['full_name' => 'Duplikat File']);
        $this->assertSame('Sudah Terdaftar', Customer::where('student_number', '20260001')->value('full_name'));
    }

    public function test_reports_invalid_rows_without_importing_them(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('admin.customers.import'), [
                'file' => $this->csv(
                    "nis,nama,kelas,no_hp,email\n"
                    .",Nama Tanpa Nis,VII A,,tanpa.nis@example.test\n"
                    ."20260001,Budi Kelas Panjang,KELAS-INI-SANGAT-PANJANG-SEKALI-LHO,,kelas.panjang@example.test\n"
                    ."20260002,,Tanpa Nama,,tanpa.nama@example.test\n"
                ),
            ])
            ->assertRedirect(route('admin.customers.import.form'))
            ->assertSessionHas('import_summary', function (array $summary): bool {
                $this->assertSame(0, $summary['imported']);
                $this->assertSame(3, $summary['failed']);
                $this->assertCount(3, $summary['issues']);
                $this->assertSame(2, $summary['issues'][0]['row']);
                $this->assertSame('Kolom nis wajib diisi.', $summary['issues'][0]['message']);
                $this->assertSame('Kolom nama wajib diisi.', $summary['issues'][2]['message']);

                return true;
            });

        $this->assertDatabaseCount('customers', 1);
    }

    public function test_supports_semicolon_delimiter(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('admin.customers.import'), [
                'file' => $this->csv(
                    "nis;nama;kelas;no_hp;email\n"
                    ."20260011;Semi Kolon;VII C;081234567011;semi.kolon@example.test\n"
                ),
            ])
            ->assertRedirect(route('admin.customers.import.form'))
            ->assertSessionHas('import_summary', function (array $summary): bool {
                $this->assertSame(1, $summary['imported']);

                return true;
            });

        $customer = Customer::where('student_number', '20260011')->firstOrFail();
        $this->assertSame('Semi Kolon', $customer->full_name);
        $this->assertSame('081234567011', $customer->phone_number);
    }

    public function test_rejects_file_with_invalid_headers(): void
    {
        $this->actingAs($this->admin, 'web')
            ->post(route('admin.customers.import'), [
                'file' => $this->csv("nomor_induk,nama_lengkap\n20260001,Budi\n"),
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Header CSV harus memuat kolom "nis", "nama", dan "email".');

        $this->assertDatabaseCount('customers', 1);
    }

    public function test_rejects_non_csv_file(): void
    {
        $path = sys_get_temp_dir().'/import-test-'.uniqid('', true).'.pdf';
        file_put_contents($path, '%PDF-fake');
        $this->tempFiles[] = $path;

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.customers.import'), [
                'file' => new UploadedFile($path, 'siswa.pdf', 'application/pdf', null, true),
            ])
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('customers', 1);
    }

    public function test_template_download_returns_csv_with_expected_columns(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->get(route('admin.customers.import.template'));

        $response->assertOk();

        $content = preg_replace('/^\xEF\xBB\xBF/', '', $response->streamedContent());
        $lines = explode("\n", trim((string) $content));

        $this->assertSame(['nis', 'nama', 'kelas', 'no_hp', 'email'], str_getcsv($lines[0]));
        $this->assertSame(['20260001', 'Budi Santoso', 'VII A', '081234567001', 'budi.santoso@example.test'], str_getcsv($lines[1]));
    }

    public function test_manual_creation_avoids_collision_with_imported_nis(): void
    {
        $year = now()->format('Y');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.customers.import'), [
                'file' => $this->csv(
                    "nis,nama,kelas,no_hp,email\n"
                    .$year."0001,Siswa Impor Satu,VII A,,impor.satu@example.test\n"
                    .$year."0002,Siswa Impor Dua,VII B,,impor.dua@example.test\n"
                ),
            ])
            ->assertSessionHas('import_summary');

        $this->actingAs($this->admin, 'web')
            ->post(route('admin.customers.store'), [
                'full_name' => 'Nasabah Manual',
                'email' => 'nasabah.manual@example.test',
                'pin' => '123456',
            ])
            ->assertRedirect(route('admin.customers.index'));

        $manual = Customer::where('full_name', 'Nasabah Manual')->firstOrFail();
        $this->assertSame($year.'0003', $manual->student_number);
    }

    public function test_non_admin_cannot_import(): void
    {
        $this->actingAsTeller()
            ->postJson(route('admin.customers.import'), [])
            ->assertForbidden();
    }
}
