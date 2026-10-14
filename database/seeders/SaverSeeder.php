<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Saver;
use App\Models\User;
use App\Services\AccountService;
use App\Services\TransactionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class SaverSeeder extends Seeder
{
    /** PIN awal untuk semua penabung contoh. */
    private const PIN = '123456';

    public function run(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();
        $teller = User::where('role', 'teller')->first();

        $savers = [
            ['2023001', 'Ani Wijaya', 'X RPL 1', '081234567001', 25000],
            ['2023002', 'Budi Santoso', 'X RPL 2', '081234567002', 50000],
            ['2023003', 'Citra Lestari', 'X TKJ 1', '081234567003', 15000],
            ['2023004', 'Dedi Kurniawan', 'X TKJ 2', '081234567004', 10000],
            ['2023005', 'Eka Puspita', 'X MM 1', '081234567005', 30000],
        ];

        foreach ($savers as [$studentNumber, $fullName, $className, $phoneNumber, $openingBalance]) {
            Member::updateOrCreate(
                ['nis' => $studentNumber],
                [
                    'full_name' => $fullName,
                    'graduation_year' => null,
                    'status' => Member::STATUS_ACCOUNT_ACTIVE,
                    'created_by' => $admin->id,
                ]
            );

            // student_number unik, jadi penabung yang pernah di-soft delete harus dipakai
            // ulang lewat withTrashed(); mencoba create lagi akan melanggar unique constraint.
            $saver = Saver::withTrashed()->updateOrCreate(
                ['student_number' => $studentNumber],
                [
                    'member_nis' => $studentNumber,
                    'full_name' => $fullName,
                    'class_name' => $className,
                    'phone_number' => $phoneNumber,
                    'pin_hash' => Hash::make(self::PIN),
                    'qr_code_value' => 'SEED-'.$studentNumber,
                    'created_by' => $admin->id,
                    'status' => 'active',
                ]
            );

            if ($saver->trashed()) {
                $saver->restore();
            }

            $this->command->info("Penabung [{$studentNumber}] {$fullName} -> PIN: ".self::PIN);

            // Rekening yang sudah ada tidak dibuat ulang, sehingga seeder aman dijalankan berkali-kali.
            if ($saver->account()->exists()) {
                continue;
            }

            if ($teller === null) {
                throw new RuntimeException('SaverSeeder butuh satu user teller untuk mencatat setoran saldo awal.');
            }

            // Nomor rekening dibuat lewat AccountService supaya memakai sequence yang sama
            // dengan aplikasi. Menghitungnya sendiri di seeder akan bertabrakan dengan nomor
            // yang sudah dibuat teller.
            $account = app(AccountService::class)->openFor($saver);

            // Saldo awal disetor lewat TransactionService agar transaksi dan jurnalnya ikut
            // tercatat. Mengisi kolom balance langsung menghasilkan saldo tanpa jejak jurnal,
            // sehingga neraca saldo tidak akan cocok dengan saldo rekening.
            app(TransactionService::class)->deposit($account, [
                'slip_number' => 'SLIP-SEED-'.$studentNumber,
                'slip_amount' => $openingBalance,
                'physical_cash_amount' => $openingBalance,
                'amount' => $openingBalance,
            ], $teller);

            $this->command->info('  rekening '.$account->account_number.' saldo awal Rp '.number_format($openingBalance));
        }
    }
}
