<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        $members = [
            ['2026001', 'Fajar Nugroho', '2026'],
            ['2026002', 'Gita Permata', '2026'],
            ['2026003', 'Hendra Saputra', '2027'],
        ];

        foreach ($members as [$nis, $fullName, $graduationYear]) {
            Member::updateOrCreate(
                ['nis' => $nis],
                [
                    'full_name' => $fullName,
                    'graduation_year' => $graduationYear,
                    'status' => Member::STATUS_ACCOUNT_INACTIVE,
                    'created_by' => $admin->id,
                ]
            );

            $this->command->info("Anggota [{$nis}] {$fullName} (belum menabung)");
        }
    }
}
