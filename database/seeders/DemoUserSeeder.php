<?php

namespace Database\Seeders;

use App\Models\Credential;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    // Static UUIDs for demo users — referenced by downstream seeders
    const SALE_USER_ID    = 'bbbbbbbb-0000-0000-0000-000000000001';
    const TRAINER_USER_ID = 'bbbbbbbb-0000-0000-0000-000000000002';

    public function run(): void
    {
        $saleRole    = Role::where('role', 'sale')->firstOrFail();
        $trainerRole = Role::where('role', 'trainer')->firstOrFail();

        // --- Sale user ---
        $sale = User::updateOrCreate(
            ['id' => self::SALE_USER_ID],
            [
                'id'          => self::SALE_USER_ID,
                'role_id'     => $saleRole->id,
                'first_name'  => 'Demo',
                'last_name'   => 'Sale',
                'dob'         => '1992-03-15',
                'address'     => '45 Sales Avenue, Phnom Penh',
                'gender'      => 'male',
                'nationality' => 'Cambodian',
            ]
        );

        if (!Credential::where('user_id', $sale->id)->exists()) {
            Credential::create([
                'user_id'      => $sale->id,
                'email'        => 'sale.demo@checkinme.com',
                'username'     => 'sale_demo',
                'phone_number' => '+85510000001',
                'password'     => Hash::make('Sale@123456'),
            ]);
        }

        // --- Trainer user ---
        $trainer = User::updateOrCreate(
            ['id' => self::TRAINER_USER_ID],
            [
                'id'          => self::TRAINER_USER_ID,
                'role_id'     => $trainerRole->id,
                'first_name'  => 'Demo',
                'last_name'   => 'Trainer',
                'dob'         => '1994-07-22',
                'address'     => '88 Training Road, Phnom Penh',
                'gender'      => 'female',
                'nationality' => 'Cambodian',
            ]
        );

        if (!Credential::where('user_id', $trainer->id)->exists()) {
            Credential::create([
                'user_id'      => $trainer->id,
                'email'        => 'trainer.demo@checkinme.com',
                'username'     => 'trainer_demo',
                'phone_number' => '+85510000002',
                'password'     => Hash::make('Trainer@123456'),
            ]);
        }

        $this->command->info('Demo users seeded: sale_demo / Sale@123456, trainer_demo / Trainer@123456');
    }
}
