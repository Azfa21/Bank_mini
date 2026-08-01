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
            ['2023001', 'Ani Wijaya', 25000],
            ['2023002', 'Budi Santoso', 50000],
            ['2023003', 'Citra Lestari', 15000],
            ['2023004', 'Dedi Kurniawan', 10000],
            ['2023005', 'Eka Puspita', 30000],
        ];

        foreach ($customers as $i => [$studentNumber, $fullName, $balance]) {
            $customer = Customer::create([
                'student_number' => $studentNumber,
                'full_name' => $fullName,
                'pin_hash' => Hash::make(str_pad((string) (123456 + $i), 6, '0', STR_PAD_LEFT)),
                'qr_token' => Str::uuid()->toString(),
                'created_by' => $admin->id,
                'status' => 'active',
            ]);

            Account::create([
                'account_number' => 'TAB-' . $studentNumber,
                'customer_id' => $customer->id,
                'balance' => $balance,
                'status' => 'active',
                'opened_at' => now(),
            ]);
        }
    }
}
