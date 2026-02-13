<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Credential;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class MockerUserSeeder extends Seeder
{
      public function run(): void
    {


        $roles = [
            'employee' => 'b45b3f6d-91c4-4e6d-8b41-2f0f52e1c222',
            'trainee'  => 'a2e4f8d9-0c35-4b6e-8d33-cc13a9c3c333',
        ];

        // EMPLOYEES
        for ($i = 1; $i <= 10; $i++) {
            $users[] = [
                'id' => match ($i) {
                    1  => '5f21e9a6-1f7a-4f6e-9b44-0c7f8f2c1e01',
                    2  => '6d0b9a8c-2e48-4c35-9f4e-2a6c8f1b3e02',
                    3  => '8a72c0b9-4d52-4e2d-9c3a-3b2e6f7a5d03',
                    4  => '9f21d8a7-3e61-4b9c-8f1a-4c2e7b5d6a04',
                    5  => '7c9e1f2a-5b48-4a3d-9e6b-5a1f3c2d4e05',
                    6  => '2b1f9c8a-6e7d-4b2a-9c8f-6e5d4a3b2c06',
                    7  => '3c4a8f2e-7b6d-4c5a-9e8d-7f6a5b4c3d07',
                    8  => '4d5b9a7c-8c9e-4d6a-9f7e-8b6a5c4d3e08',
                    9  => '5e6c1b9d-9d8a-4e7b-8a9c-9d7b6c5e4f09',
                    10 => '6f7d2c0e-0e9b-4f8c-9b0d-a8c7e6f5d4c0',
                },
                'role' => 'employee',
                'first_name' => "Employee{$i}",
                'last_name' => 'User',
                'gender' => 'male',
            ];
        }

        // TRAINEES
        for ($i = 1; $i <= 10; $i++) {
            $users[] = [
                'id' => match ($i) {
                    1  => 'a1c2e3f4-1111-4a1b-9c11-b1c2d3e4f001',
                    2  => 'b2d3f4a5-2222-4b2c-8d22-c2d3e4f5a002',
                    3  => 'c3e4a5b6-3333-4c3d-9e33-d3e4f5a6b003',
                    4  => 'd4f5b6c7-4444-4d4e-8f44-e4f5a6b7c004',
                    5  => 'e5a6c7d8-5555-4e5f-9a55-f5a6b7c8d005',
                    6  => 'f6b7d8e9-6666-4f6a-8b66-a6b7c8d9e006',
                    7  => 'a7c8e9f0-7777-4a7b-9c77-b7c8d9e0f007',
                    8  => 'b8d9f0a1-8888-4b8c-8d88-c8d9e0f1a008',
                    9  => 'c9e0a1b2-9999-4c9d-9e99-d9e0f1a2b009',
                    10 => 'd0f1b2c3-aaaa-4d0e-8faa-e0f1a2b3c00a',
                },
                'role' => 'trainee',
                'first_name' => "Trainee{$i}",
                'last_name' => 'User',
                'gender' => 'female',
            ];
        }

        foreach ($users as $index => $user) {

            DB::table('users')->insertOrIgnore([
                'id'           => $user['id'],
                'role_id'      => $roles[$user['role']],
                'first_name'   => $user['first_name'],
                'last_name'    => $user['last_name'],
                'dob'          => '2000-01-01',
                'address'      => 'Phnom Penh',
                'gender'       => $user['gender'],
                'nationality'  => 'Cambodian',
                'is_suspended' => false,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            DB::table('credentials')->insertOrIgnore([
                'id'           => $this->credentialUuid($index),
                'user_id'      => $user['id'],
                'email'        => strtolower($user['first_name']) . '@example.com',
                'username'     => strtolower($user['first_name']),
                'phone_number' => '+855' . str_pad((string) (12345678 + $index), 8, '0', STR_PAD_LEFT),
                'password'     => Hash::make('Password@123'),
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            DB::table('personal_information')->insertOrIgnore([
                'id'         => $this->personalUuid($index),
                'user_id'    => $user['id'],
                'professional_photo' => 'documents/professtional_photos/WFGFLJcDNoTX5LEoWni4TCsHxyUjXFTE8E4cZTVP.jpg',
                'nationality_card' => 'documents/nationality_cards/IiVWZgfL6Q6wEFAgknb3rePEuQsCBvkLprFNDEUc.jpg',
                'family_book' => 'document/family_books/5ZemXzhCMHdCGLrqObS5YrgqNQl4zE6uSLBJgMel.jpg',
                'degree_certification' => 'document/degree_certificates/lIjlMuVaFKbAJMRCxRL9DcAy43Nj1JP9eVQKXjfV.jpg',
                'social_media' => 'https://facebook.com/' . strtolower($user['first_name']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('emergency_contact')->insertOrIgnore([
                'id'                   => $this->emergencyUuid($index),
                'user_id'              => $user['id'],
                'contact_first_name'   => 'Emergency',
                'contact_last_name'    => 'Contact',
                'contact_relationship' => 'Parent',
                'contact_phone_number' => '+85512999999',
                'contact_address'      => 'Phnom Penh',
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        }
    }

    private function credentialUuid(int $i): string
    {
        return match ($i) {
            0  => '0a1b2c3d-aaaa-4a11-8aaa-111111111111',
            default => '0a1b2c3d-bbbb-4b22-8bbb-' . str_pad($i, 12, '0', STR_PAD_LEFT),
        };
    }

    private function personalUuid(int $i): string
    {
        return match ($i) {
            0  => '1b2c3d4e-cccc-4c33-8ccc-222222222222',
            default => '1b2c3d4e-dddd-4d44-8ddd-' . str_pad($i, 12, '0', STR_PAD_LEFT),
        };
    }

    private function emergencyUuid(int $i): string
    {
        return match ($i) {
            0  => '2c3d4e5f-eeee-4e55-8eee-333333333333',
            default => '2c3d4e5f-ffff-4f66-8fff-' . str_pad($i, 12, '0', STR_PAD_LEFT),
        };
    }
}
