<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Run order matters — each seeder depends on data from the previous ones.
     */
    public function run(): void
    {
        $this->call([
            // 1. Auth & core users
            AdminSeeder::class,           // roles (migration) + admin user

            // 2. Systems (idempotent — safe to run after migration seeding)
            SystemSeeder::class,

            // 3. Demo internal users (sale + trainer)
            DemoUserSeeder::class,

            // 4. Media assets
            MediaSeeder::class,

            // 5. Clients & contacts
            ClientSeeder::class,
            ClientContactSeeder::class,

            // 6. Training stages
            OnboardingStageSeeder::class,

            // 7. Onboarding workflow
            OnboardingRequestSeeder::class,
            TrainingAssignmentSeeder::class,
            TrainingSessionSeeder::class,

            // 8. Session details
            SessionAttendeeSeeder::class,
            StageProgressSeeder::class,
        ]);
    }
}
