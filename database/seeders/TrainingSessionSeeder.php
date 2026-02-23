<?php

namespace Database\Seeders;

use App\Models\TrainingSession;
use Illuminate\Database\Seeder;

class TrainingSessionSeeder extends Seeder
{
    const SESSION_1_ID = '33333333-aaaa-0000-0000-000000000001';

    const SESSION_2_ID = '33333333-aaaa-0000-0000-000000000002';

    public function run(): void
    {
        $sessions = [
            [
                'id' => self::SESSION_1_ID,
                'assignment_id' => TrainingAssignmentSeeder::ASSIGNMENT_1_ID,
                'stage_id' => OnboardingStageSeeder::STAGE_INITIAL_SETUP_ID,
                'session_title' => 'Initial Setup — Day 1',
                'session_description' => 'Server environment setup, database configuration, and initial admin account creation.',
                'scheduled_date' => '2026-02-20',
                'scheduled_start_time' => '09:00:00',
                'scheduled_end_time' => '12:00:00',
                'actual_start_time' => null,
                'actual_end_time' => null,
                'location_type' => 'online',
                'meeting_link' => 'https://meet.google.com/abc-defg-hij',
                'physical_location' => null,
                'status' => 'scheduled',
                'completion_notes' => null,
            ],
            [
                'id' => self::SESSION_2_ID,
                'assignment_id' => TrainingAssignmentSeeder::ASSIGNMENT_1_ID,
                'stage_id' => OnboardingStageSeeder::STAGE_BASIC_TRAINING_ID,
                'session_title' => 'Basic Training — Core Modules Overview',
                'session_description' => 'Walkthrough of dashboard, employee management, attendance, and leave modules.',
                'scheduled_date' => '2026-02-22',
                'scheduled_start_time' => '09:00:00',
                'scheduled_end_time' => '17:00:00',
                'actual_start_time' => null,
                'actual_end_time' => null,
                'location_type' => 'onsite',
                'meeting_link' => null,
                'physical_location' => 'Street 271, Sangkat Toul Tum Poung, Khan Chamkarmon, Phnom Penh (Alpha Tech HQ)',
                'status' => 'scheduled',
                'completion_notes' => null,
            ],
        ];

        foreach ($sessions as $data) {
            TrainingSession::updateOrCreate(['id' => $data['id']], $data);
        }

        $this->command->info('Training sessions seeded: 2 sessions for ASSIGNMENT_1');
    }
}
