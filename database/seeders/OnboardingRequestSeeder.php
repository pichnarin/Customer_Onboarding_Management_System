<?php

namespace Database\Seeders;

use App\Models\OnboardingRequest;
use Illuminate\Database\Seeder;

class OnboardingRequestSeeder extends Seeder
{
    const REQUEST_1_ID = '11111111-aaaa-0000-0000-000000000001';
    const REQUEST_2_ID = '11111111-aaaa-0000-0000-000000000002';

    public function run(): void
    {
        $requests = [
            [
                'id'                  => self::REQUEST_1_ID,
                'request_code'        => 'REQ-2026-0001',
                'client_id'           => ClientSeeder::CLIENT_ALPHA_ID,
                'system_id'           => SystemSeeder::COMS_ID,
                'created_by_user_id'  => DemoUserSeeder::SALE_USER_ID,
                'priority'            => 'high',
                'status'              => 'assigned',
                'notes'               => 'Client is migrating from legacy HR system. Requires extra attention during initial setup.',
                'expected_start_date' => '2026-02-20',
                'expected_end_date'   => '2026-03-06',
                'actual_start_date'   => null,
                'actual_end_date'     => null,
            ],
            [
                'id'                  => self::REQUEST_2_ID,
                'request_code'        => 'REQ-2026-0002',
                'client_id'           => ClientSeeder::CLIENT_BETA_ID,
                'system_id'           => SystemSeeder::COMS_ID,
                'created_by_user_id'  => DemoUserSeeder::SALE_USER_ID,
                'priority'            => 'medium',
                'status'              => 'pending',
                'notes'               => 'New client onboarding. Standard COMS training package.',
                'expected_start_date' => '2026-03-01',
                'expected_end_date'   => '2026-03-15',
                'actual_start_date'   => null,
                'actual_end_date'     => null,
            ],
        ];

        foreach ($requests as $data) {
            OnboardingRequest::updateOrCreate(['id' => $data['id']], $data);
        }

        $this->command->info('Onboarding requests seeded: REQ-2026-0001, REQ-2026-0002');
    }
}
