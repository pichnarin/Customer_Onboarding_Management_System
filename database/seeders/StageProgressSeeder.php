<?php

namespace Database\Seeders;

use App\Models\StageProgress;
use Illuminate\Database\Seeder;

class StageProgressSeeder extends Seeder
{
    const PROGRESS_1_ID = '55555555-aaaa-0000-0000-000000000001';

    const PROGRESS_2_ID = '55555555-aaaa-0000-0000-000000000002';

    const PROGRESS_3_ID = '55555555-aaaa-0000-0000-000000000003';

    const PROGRESS_4_ID = '55555555-aaaa-0000-0000-000000000004';

    const PROGRESS_5_ID = '55555555-aaaa-0000-0000-000000000005';

    public function run(): void
    {
        $progressRecords = [
            [
                'id' => self::PROGRESS_1_ID,
                'assignment_id' => TrainingAssignmentSeeder::ASSIGNMENT_1_ID,
                'stage_id' => OnboardingStageSeeder::STAGE_INITIAL_SETUP_ID,
                'status' => 'in_progress',
                'progress_percentage' => 0.00,
                'started_at' => null,
                'completed_at' => null,
                'notes' => 'Scheduled for 2026-02-20',
            ],
            [
                'id' => self::PROGRESS_2_ID,
                'assignment_id' => TrainingAssignmentSeeder::ASSIGNMENT_1_ID,
                'stage_id' => OnboardingStageSeeder::STAGE_BASIC_TRAINING_ID,
                'status' => 'not_started',
                'progress_percentage' => 0.00,
                'started_at' => null,
                'completed_at' => null,
                'notes' => null,
            ],
            [
                'id' => self::PROGRESS_3_ID,
                'assignment_id' => TrainingAssignmentSeeder::ASSIGNMENT_1_ID,
                'stage_id' => OnboardingStageSeeder::STAGE_ADV_TRAINING_ID,
                'status' => 'not_started',
                'progress_percentage' => 0.00,
                'started_at' => null,
                'completed_at' => null,
                'notes' => null,
            ],
            [
                'id' => self::PROGRESS_4_ID,
                'assignment_id' => TrainingAssignmentSeeder::ASSIGNMENT_1_ID,
                'stage_id' => OnboardingStageSeeder::STAGE_ASSESSMENT_ID,
                'status' => 'not_started',
                'progress_percentage' => 0.00,
                'started_at' => null,
                'completed_at' => null,
                'notes' => null,
            ],
            [
                'id' => self::PROGRESS_5_ID,
                'assignment_id' => TrainingAssignmentSeeder::ASSIGNMENT_1_ID,
                'stage_id' => OnboardingStageSeeder::STAGE_HANDOVER_ID,
                'status' => 'not_started',
                'progress_percentage' => 0.00,
                'started_at' => null,
                'completed_at' => null,
                'notes' => null,
            ],
        ];

        foreach ($progressRecords as $data) {
            StageProgress::updateOrCreate(
                ['assignment_id' => $data['assignment_id'], 'stage_id' => $data['stage_id']],
                $data
            );
        }

        $this->command->info('Stage progress seeded: 5 records for ASSIGNMENT_1 (all 5 COMS stages)');
    }
}
