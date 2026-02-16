<?php

namespace Database\Seeders;

use App\Models\TrainingAssignment;
use Illuminate\Database\Seeder;

class TrainingAssignmentSeeder extends Seeder
{
    const ASSIGNMENT_1_ID = '22222222-aaaa-0000-0000-000000000001';

    public function run(): void
    {
        $assignments = [
            [
                'id'                     => self::ASSIGNMENT_1_ID,
                'onboarding_request_id'  => OnboardingRequestSeeder::REQUEST_1_ID,
                'trainer_id'             => DemoUserSeeder::TRAINER_USER_ID,
                'assigned_by_user_id'    => DemoUserSeeder::SALE_USER_ID,
                'assigned_at'            => '2026-02-17 09:00:00',
                'status'                 => 'accepted',
                'notes'                  => 'Trainer has prior experience with Alpha Tech. Prioritise initial setup stage.',
                'accepted_at'            => '2026-02-17 10:30:00',
                'started_at'             => null,
                'completed_at'           => null,
                'rejection_reason'       => null,
            ],
        ];

        foreach ($assignments as $data) {
            TrainingAssignment::updateOrCreate(['id' => $data['id']], $data);
        }

        $this->command->info('Training assignments seeded: 1 assignment for REQ-2026-0001');
    }
}
