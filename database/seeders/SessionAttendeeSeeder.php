<?php

namespace Database\Seeders;

use App\Models\SessionAttendee;
use Illuminate\Database\Seeder;

class SessionAttendeeSeeder extends Seeder
{
    const ATTENDEE_1_ID = '44444444-aaaa-0000-0000-000000000001';

    const ATTENDEE_2_ID = '44444444-aaaa-0000-0000-000000000002';

    const ATTENDEE_3_ID = '44444444-aaaa-0000-0000-000000000003';

    const ATTENDEE_4_ID = '44444444-aaaa-0000-0000-000000000004';

    public function run(): void
    {
        $attendees = [
            // Session 1 attendees
            [
                'id' => self::ATTENDEE_1_ID,
                'session_id' => TrainingSessionSeeder::SESSION_1_ID,
                'client_contact_id' => ClientContactSeeder::ALPHA_CONTACT_1_ID,
                'attendance_status' => 'confirmed',
                'attended_at' => null,
                'notes' => 'Primary contact — must attend all sessions.',
            ],
            [
                'id' => self::ATTENDEE_2_ID,
                'session_id' => TrainingSessionSeeder::SESSION_1_ID,
                'client_contact_id' => ClientContactSeeder::ALPHA_CONTACT_2_ID,
                'attendance_status' => 'invited',
                'attended_at' => null,
                'notes' => null,
            ],
            // Session 2 attendees
            [
                'id' => self::ATTENDEE_3_ID,
                'session_id' => TrainingSessionSeeder::SESSION_2_ID,
                'client_contact_id' => ClientContactSeeder::ALPHA_CONTACT_1_ID,
                'attendance_status' => 'confirmed',
                'attended_at' => null,
                'notes' => null,
            ],
            [
                'id' => self::ATTENDEE_4_ID,
                'session_id' => TrainingSessionSeeder::SESSION_2_ID,
                'client_contact_id' => ClientContactSeeder::ALPHA_CONTACT_2_ID,
                'attendance_status' => 'invited',
                'attended_at' => null,
                'notes' => null,
            ],
        ];

        foreach ($attendees as $data) {
            SessionAttendee::updateOrCreate(
                ['session_id' => $data['session_id'], 'client_contact_id' => $data['client_contact_id']],
                $data
            );
        }

        $this->command->info('Session attendees seeded: 4 records across 2 sessions');
    }
}
