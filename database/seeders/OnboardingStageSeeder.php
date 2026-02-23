<?php

namespace Database\Seeders;

use App\Models\OnboardingStage;
use Illuminate\Database\Seeder;

class OnboardingStageSeeder extends Seeder
{
    // COMS stages
    const STAGE_INITIAL_SETUP_ID = 'ffffffff-0000-0000-0000-000000000001';

    const STAGE_BASIC_TRAINING_ID = 'ffffffff-0000-0000-0000-000000000002';

    const STAGE_ADV_TRAINING_ID = 'ffffffff-0000-0000-0000-000000000003';

    const STAGE_ASSESSMENT_ID = 'ffffffff-0000-0000-0000-000000000004';

    const STAGE_HANDOVER_ID = 'ffffffff-0000-0000-0000-000000000005';

    public function run(): void
    {
        $stages = [
            [
                'id' => self::STAGE_INITIAL_SETUP_ID,
                'name' => 'Initial Setup',
                'description' => 'System installation, configuration, and environment preparation.',
                'sequence_order' => 1,
                'estimated_duration_days' => 2,
                'system_id' => SystemSeeder::COMS_ID,
                'is_active' => true,
            ],
            [
                'id' => self::STAGE_BASIC_TRAINING_ID,
                'name' => 'Basic Training',
                'description' => 'Introduction to core modules and basic feature walkthrough.',
                'sequence_order' => 2,
                'estimated_duration_days' => 3,
                'system_id' => SystemSeeder::COMS_ID,
                'is_active' => true,
            ],
            [
                'id' => self::STAGE_ADV_TRAINING_ID,
                'name' => 'Advanced Training',
                'description' => 'Deep dive into advanced features, reporting, and customization.',
                'sequence_order' => 3,
                'estimated_duration_days' => 5,
                'system_id' => SystemSeeder::COMS_ID,
                'is_active' => true,
            ],
            [
                'id' => self::STAGE_ASSESSMENT_ID,
                'name' => 'Assessment',
                'description' => 'Practical assessment to evaluate trainee understanding and readiness.',
                'sequence_order' => 4,
                'estimated_duration_days' => 1,
                'system_id' => SystemSeeder::COMS_ID,
                'is_active' => true,
            ],
            [
                'id' => self::STAGE_HANDOVER_ID,
                'name' => 'Handover & Go-Live',
                'description' => 'Final handover, documentation delivery, and go-live sign-off.',
                'sequence_order' => 5,
                'estimated_duration_days' => 1,
                'system_id' => SystemSeeder::COMS_ID,
                'is_active' => true,
            ],
        ];

        foreach ($stages as $data) {
            OnboardingStage::updateOrCreate(['id' => $data['id']], $data);
        }

        $this->command->info('Onboarding stages seeded: 5 COMS stages');
    }
}
