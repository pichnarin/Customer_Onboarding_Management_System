<?php

namespace App\Services\Onboarding;

use App\Exceptions\Business\SessionOverlapException;
use App\Models\SessionAttendee;
use App\Models\SessionStudent;
use App\Models\StageProgress;
use App\Models\TrainingAssignment;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\Logging\ActivityLogger;
use App\Services\Notification\NotificationService;
use App\Services\Notification\TelegramService;
use Illuminate\Support\Facades\DB;

class TrainingService
{
    public function __construct(
        private StatusManager $statusManager,
        private NotificationService $notificationService,
        private TelegramService $telegramService,
        private ActivityLogger $activityLogger
    ) {}

    private function checkOverlap(
        string $trainerId,
        string $date,
        string $startTime,
        string $endTime,
        ?string $excludeSessionId = null
    ): void {
        $exists = TrainingSession::whereHas('assignment',
            fn ($q) => $q->where('trainer_id', $trainerId))
            ->where('scheduled_date', $date)
            ->whereNotIn('status', ['cancelled', 'rescheduled'])
            ->where('scheduled_start_time', '<', $endTime)
            ->where('scheduled_end_time', '>', $startTime)
            ->when($excludeSessionId, fn ($q) => $q->where('id', '!=', $excludeSessionId))
            ->exists();

        if ($exists) {
            throw new SessionOverlapException;
        }
    }

    public function acceptAssignment(TrainingAssignment $assignment): void
    {
        $this->statusManager->validateTransition($assignment, 'accepted');

        $assignment->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        $salesId = $assignment->onboardingRequest?->created_by_user_id;
        if ($salesId) {
            $this->notificationService->notify(
                [$salesId],
                'assignment_accepted',
                'Assignment Accepted',
                "Trainer has accepted assignment for request {$assignment->onboardingRequest->request_code}",
                ['type' => 'training_assignment', 'id' => $assignment->id]
            );
        }

        $this->activityLogger->log(
            ActivityLogger::ASSIGNMENT_ACCEPTED,
            "Assignment {$assignment->id} accepted",
            ['assignment_id' => $assignment->id]
        );
    }

    public function rejectAssignment(TrainingAssignment $assignment, string $reason): void
    {
        $this->statusManager->validateTransition($assignment, 'rejected');

        DB::transaction(function () use ($assignment, $reason) {
            $assignment->update([
                'rejected_at' => now(),
                'status' => 'rejected',
                'rejection_reason' => $reason,
            ]);

            $request = $assignment->onboardingRequest;
            if ($request) {
                $request->update(['status' => 'pending']);
            }

            $salesId = $request?->created_by_user_id;
            if ($salesId) {
                $this->notificationService->notify(
                    [$salesId],
                    'assignment_rejected',
                    'Assignment Rejected',
                    "Trainer rejected assignment for request {$request->request_code}. Reason: {$reason}",
                    ['type' => 'training_assignment', 'id' => $assignment->id]
                );
            }

            $this->activityLogger->log(
                ActivityLogger::ASSIGNMENT_REJECTED,
                "Assignment {$assignment->id} rejected. Reason: {$reason}",
                ['assignment_id' => $assignment->id]
            );
        });
    }

    public function createSession(TrainingAssignment $assignment, array $data, string $creatorId): TrainingSession
    {
        return DB::transaction(function () use ($assignment, $data, $creatorId) {
            $contactIds = $data['contact_ids'] ?? [];
            unset($data['contact_ids']);

            $this->checkOverlap(
                $assignment->trainer_id,
                $data['scheduled_date'],
                $data['scheduled_start_time'],
                $data['scheduled_end_time']
            );

            $data['assignment_id'] = $assignment->id;
            $data['status'] = 'scheduled';
            $data['creator_id'] = $creatorId;

            $session = TrainingSession::create($data);

            // Bulk-insert attendees
            if (! empty($contactIds)) {
                $now = now();
                $rows = array_map(fn ($contactId) => [
                    'id' => (string) \Illuminate\Support\Str::uuid(),
                    'session_id' => $session->id,
                    'client_contact_id' => $contactId,
                    'attendance_status' => 'invited',
                    'attended_at' => null,
                    'notes' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ], $contactIds);

                SessionAttendee::insert($rows);
            }

            $this->telegramService->sendSessionNotification($session, 'session_scheduled');

            // Notify sale agent
            $salesId = $assignment->onboardingRequest?->created_by_user_id;
            if ($salesId) {
                $clientName = $assignment->onboardingRequest?->client?->company_name ?? 'client';
                $this->notificationService->notify(
                    [$salesId],
                    'session_created',
                    'Session Scheduled',
                    "Trainer has scheduled a new session '{$session->session_title}' for {$clientName} on {$session->scheduled_date->toDateString()}",
                    ['type' => 'training_session', 'id' => $session->id]
                );
            }

            $this->activityLogger->log(
                ActivityLogger::SESSION_CREATED,
                "Session '{$session->session_title}' created for assignment {$assignment->id}",
                ['session_id' => $session->id, 'assignment_id' => $assignment->id]
            );

            return $session;
        });
    }

    public function startSession(
        TrainingSession $session,
        string $startProofMediaId,
        float $startLatitude,
        float $startLongitude
    ): void {
        $this->statusManager->validateTransition($session, 'in_progress');

        DB::transaction(function () use ($session, $startProofMediaId, $startLatitude, $startLongitude) {
            $session->update([
                'status'               => 'in_progress',
                'actual_start_time'    => now(),
                'start_proof_media_id' => $startProofMediaId,
                'start_latitude'       => $startLatitude,
                'start_longitude'      => $startLongitude,
            ]);

            $assignment = $session->assignment;

            // If first session: cascade statuses up
            if ($assignment->status === 'accepted') {
                $this->statusManager->validateTransition($assignment, 'in_progress');
                $assignment->update([
                    'status' => 'in_progress',
                    'started_at' => now(),
                ]);

                $request = $assignment->onboardingRequest;
                if ($request && $request->status === 'assigned') {
                    $request->update([
                        'status' => 'in_progress',
                        'actual_start_date' => now()->toDateString(),
                    ]);
                }
            }

            // Update stage progress to in_progress if not started
            $stageProgress = StageProgress::where('assignment_id', $assignment->id)
                ->where('stage_id', $session->stage_id)
                ->first();

            if ($stageProgress && $stageProgress->status === 'not_started') {
                $stageProgress->update([
                    'status' => 'in_progress',
                    'started_at' => now(),
                ]);
            }

            // Notify sale agent
            $salesId = $assignment->onboardingRequest?->created_by_user_id;
            if ($salesId) {
                $clientName = $assignment->onboardingRequest?->client?->company_name ?? 'client';
                $this->notificationService->notify(
                    [$salesId],
                    'session_started',
                    'Session Started',
                    "Trainer has started session '{$session->session_title}' for {$clientName}",
                    ['type' => 'training_session', 'id' => $session->id]
                );
            }

            $this->activityLogger->log(
                ActivityLogger::SESSION_STARTED,
                "Session '{$session->session_title}' started",
                ['session_id' => $session->id]
            );
        });
    }

    public function markAttendance(SessionAttendee $attendee, string $status, ?string $notes): void
    {
        $update = ['attendance_status' => $status, 'notes' => $notes];

        if ($status === 'attended') {
            $update['attended_at'] = now();
        }

        $attendee->update($update);

        $this->activityLogger->log(
            ActivityLogger::ATTENDANCE_MARKED,
            "Attendance marked as '{$status}' for attendee {$attendee->id}",
            ['attendee_id' => $attendee->id, 'status' => $status]
        );
    }

    public function completeSession(
        TrainingSession $session,
        string $notes,
        string $endProofMediaId,
        int $studentCount,
        float $endLatitude,
        float $endLongitude
    ): void {
        $this->statusManager->validateTransition($session, 'completed');

        DB::transaction(function () use ($session, $notes, $endProofMediaId, $studentCount, $endLatitude, $endLongitude) {
            $session->update([
                'status'             => 'completed',
                'actual_end_time'    => now(),
                'completion_notes'   => $notes,
                'end_proof_media_id' => $endProofMediaId,
                'student_count'      => $studentCount,
                'end_latitude'       => $endLatitude,
                'end_longitude'      => $endLongitude,
            ]);

            $this->statusManager->cascadeOnSessionComplete($session);

            $assignment = $session->assignment;
            $salesId = $assignment->onboardingRequest?->created_by_user_id;

            if ($salesId) {
                $this->notificationService->notify(
                    [$salesId],
                    'session_completed',
                    'Session Completed',
                    "Session '{$session->session_title}' has been completed with {$studentCount} student(s)",
                    ['type' => 'training_session', 'id' => $session->id]
                );
            }

            $this->activityLogger->log(
                ActivityLogger::SESSION_COMPLETED,
                "Session '{$session->session_title}' completed",
                ['session_id' => $session->id]
            );
        });
    }

    public function rescheduleSession(TrainingSession $session, array $newSchedule): TrainingSession
    {
        $this->statusManager->validateTransition($session, 'rescheduled');

        return DB::transaction(function () use ($session, $newSchedule) {
            $assignment = $session->assignment;
            $trainerId = $assignment->trainer_id;

            $this->checkOverlap(
                $trainerId,
                $newSchedule['scheduled_date'],
                $newSchedule['scheduled_start_time'],
                $newSchedule['scheduled_end_time'],
                $session->id
            );

            $rescheduleReason = $newSchedule['reschedule_reason'] ?? null;

            $session->update([
                'status' => 'rescheduled',
                'reschedule_reason' => $rescheduleReason,
            ]);

            // Clone session with new schedule
            $newSession = TrainingSession::create([
                'assignment_id' => $session->assignment_id,
                'stage_id' => $session->stage_id,
                'session_title' => $session->session_title,
                'session_description' => $session->session_description,
                'scheduled_date' => $newSchedule['scheduled_date'],
                'scheduled_start_time' => $newSchedule['scheduled_start_time'],
                'scheduled_end_time' => $newSchedule['scheduled_end_time'],
                'location_type' => $session->location_type,
                'meeting_link' => $newSchedule['meeting_link'] ?? $session->meeting_link,
                'physical_location' => $newSchedule['physical_location'] ?? $session->physical_location,
                'status' => 'scheduled',
            ]);

            // Re-invite original attendees
            $now = now();
            $attendees = $session->attendees()->with('clientContact')->get();
            $rows = $attendees->map(fn ($att) => [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'session_id' => $newSession->id,
                'client_contact_id' => $att->client_contact_id,
                'attendance_status' => 'invited',
                'attended_at' => null,
                'notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->toArray();

            if (! empty($rows)) {
                SessionAttendee::insert($rows);
            }

            $this->telegramService->sendSessionNotification($session, 'session_rescheduled');

            // Notify sale agent
            $salesId = $assignment->onboardingRequest?->created_by_user_id;
            if ($salesId) {
                $oldDate = $session->scheduled_date->toDateString();
                $newDate = $newSchedule['scheduled_date'];
                $this->notificationService->notify(
                    [$salesId],
                    'session_rescheduled',
                    'Session Rescheduled',
                    "Trainer rescheduled '{$session->session_title}' — old: {$oldDate}, new: {$newDate}. Reason: ".($rescheduleReason ?? 'N/A'),
                    ['type' => 'training_session', 'id' => $newSession->id]
                );
            }

            $this->activityLogger->log(
                ActivityLogger::SESSION_RESCHEDULED,
                "Session '{$session->session_title}' rescheduled",
                ['old_session_id' => $session->id, 'new_session_id' => $newSession->id]
            );

            return $newSession;
        });
    }

    public function cancelSession(TrainingSession $session, string $reason, string $cancelledByUserId): void
    {
        $this->statusManager->validateTransition($session, 'cancelled');

        DB::transaction(function () use ($session, $reason, $cancelledByUserId) {
            $session->update([
                'status' => 'cancelled',
                'cancellation_reason' => $reason,
                'cancelled_by_user_id' => $cancelledByUserId,
                'cancelled_at' => now(),
            ]);

            $session->attendees()
                ->whereIn('attendance_status', ['invited', 'confirmed'])
                ->update(['attendance_status' => 'cancelled']);

            $this->telegramService->sendSessionNotification($session, 'session_cancelled');

            // Notify sale agent
            $assignment = $session->assignment;
            $salesId = $assignment->onboardingRequest?->created_by_user_id;
            if ($salesId) {
                $this->notificationService->notify(
                    [$salesId],
                    'session_cancelled',
                    'Session Cancelled',
                    "Trainer cancelled session '{$session->session_title}' — Reason: {$reason}",
                    ['type' => 'training_session', 'id' => $session->id]
                );
            }

            $this->activityLogger->log(
                ActivityLogger::SESSION_CANCELLED,
                "Session '{$session->session_title}' cancelled",
                ['session_id' => $session->id]
            );
        });
    }

    public function addStudents(TrainingSession $session, array $students): void
    {
        $now = now();
        $rows = array_map(fn ($student) => [
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'session_id' => $session->id,
            'name' => $student['name'] ?? null,
            'phone_number' => $student['phone_number'] ?? null,
            'profession' => $student['profession'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ], $students);

        SessionStudent::insert($rows);

        $count = count($students);

        // Notify sale agent
        $assignment = $session->assignment;
        $salesId = $assignment->onboardingRequest?->created_by_user_id;
        if ($salesId) {
            $this->notificationService->notify(
                [$salesId],
                'student_attendance_submitted',
                'Attendance Details Submitted',
                "Attendance details submitted for '{$session->session_title}' — {$count} student(s)",
                ['type' => 'training_session', 'id' => $session->id]
            );
        }

        $this->activityLogger->log(
            ActivityLogger::ATTENDANCE_MARKED,
            "Attendance details submitted for session '{$session->session_title}' — {$count} student(s)",
            ['session_id' => $session->id, 'student_count' => $count]
        );
    }

    public function skipStage(StageProgress $progress, string $reason): void
    {
        $progress->update([
            'status' => 'skipped',
            'notes' => $reason,
        ]);

        // Re-check cascade: load a dummy completed session context
        // We trigger cascade by checking assignment status directly
        $assignment = $progress->assignment;

        $allStagesDone = StageProgress::where('assignment_id', $assignment->id)
            ->whereNotIn('status', ['completed', 'skipped'])
            ->doesntExist();

        if ($allStagesDone && $assignment->status === 'in_progress') {
            $assignment->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $request = $assignment->onboardingRequest;
            if ($request && $request->status === 'in_progress') {
                $request->update([
                    'status' => 'completed',
                    'actual_end_date' => now()->toDateString(),
                ]);
            }
        }

        $this->activityLogger->log(
            ActivityLogger::STAGE_SKIPPED,
            "Stage progress {$progress->id} skipped. Reason: {$reason}",
            ['stage_progress_id' => $progress->id]
        );
    }

    public function getTrainerDashboard(User $trainer): array
    {
        $assignments = TrainingAssignment::with([
            'onboardingRequest.client',
            'onboardingRequest.system',
            'stageProgress',
            'sessions' => fn ($q) => $q->orderBy('scheduled_date'),
        ])->where('trainer_id', $trainer->id)
            ->orderByDesc('assigned_at')
            ->get();

        return $assignments->map(function ($assignment) {
            $request = $assignment->onboardingRequest;
            $progress = $this->statusManager->calculateOverallProgress($assignment);

            return [
                'id' => $assignment->id,
                'status' => $assignment->status,
                'request_code' => $request?->request_code,
                'client' => $request?->client?->company_name,
                'system' => $request?->system?->name,
                'overall_progress' => $progress,
                'next_session' => $assignment->sessions
                    ->where('status', 'scheduled')
                    ->first(),
            ];
        })->values()->toArray();
    }

    public function listSessions(string $assignmentId): \Illuminate\Support\Collection
    {
        return TrainingSession::where('assignment_id', $assignmentId)
            ->with(['stage', 'attendees.clientContact'])
            ->orderBy('scheduled_date')
            ->orderBy('scheduled_start_time')
            ->get();
    }

    public function getAssignment(string $assignmentId): TrainingAssignment
    {
        return TrainingAssignment::with([
            'onboardingRequest.client',
            'trainer',
            'stageProgress.stage',
            'sessions',
        ])->findOrFail($assignmentId);
    }

    public function listAssignments(User $trainer): \Illuminate\Support\Collection
    {
        return TrainingAssignment::with(['onboardingRequest.client', 'onboardingRequest.system'])
            ->where('trainer_id', $trainer->id)
            ->orderByDesc('assigned_at')
            ->get();
    }
}
