<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $customers = [
            ['2023001', 'Ani Wijaya', 'X RPL 1', '081234567001', 25000],
            ['2023002', 'Budi Santoso', 'X RPL 2', '081234567002', 50000],
            ['2023003', 'Citra Lestari', 'X TKJ 1', '081234567003', 15000],
            ['2023004', 'Dedi Kurniawan', 'X TKJ 2', '081234567004', 10000],
            ['2023005', 'Eka Puspita', 'X MM 1', '081234567005', 30000],
        ];

        foreach ($customers as $i => [$studentNumber, $fullName, $className, $phoneNumber, $balance]) {
            $customer = Customer::create([
                'student_number' => $studentNumber,
                'full_name' => $fullName,
                'class_name' => $className,
                'phone_number' => $phoneNumber,
                'pin_hash' => Hash::make('123456'),
                'qr_code_value' => Str::uuid()->toString(),
                'created_by' => $admin->id,
                'status' => 'active',
            ]);

            $this->command->info("Nasabah [{$studentNumber}] {$fullName} -> PIN: 123456");

            $academicYear = date('Y').'/'.(date('Y') + 1);
            $accountNumber = $academicYear.'-'.str_pad($i + 1, 3, '0', STR_PAD_LEFT);

            Account::create([
                'account_number' => $accountNumber,
                'customer_id' => $customer->id,
                'balance' => $balance,
                'opening_balance' => $balance,
                'minimum_balance' => 10000,
                'academic_year' => $academicYear,
                'status' => 'active',
                'opened_at' => now(),
            ]);
        }
    }
}
