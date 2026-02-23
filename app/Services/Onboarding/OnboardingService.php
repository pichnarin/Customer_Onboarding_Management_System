<?php

namespace App\Services\Onboarding;

use App\Models\OnboardingRequest;
use App\Models\OnboardingStage;
use App\Models\StageProgress;
use App\Models\TrainingAssignment;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\Logging\ActivityLogger;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OnboardingService
{
    public function __construct(
        private StatusManager $statusManager,
        private NotificationService $notificationService,
        private ActivityLogger $activityLogger
    ) {}

    public function createRequest(array $data): OnboardingRequest
    {
        return DB::transaction(function () use ($data) {
            $data['request_code'] = $this->statusManager->generateRequestCode();
            $data['status'] = 'pending';

            $request = OnboardingRequest::create($data);

            // Bulk-insert stage_progress rows for all stages of the system
            $stages = OnboardingStage::where('system_id', $data['system_id'])
                ->where('is_active', true)
                ->orderBy('sequence_order')
                ->get();

            $now = now();
            $rows = $stages->map(fn ($stage) => [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'assignment_id' => null,
                'stage_id' => $stage->id,
                'status' => 'not_started',
                'progress_percentage' => 0.00,
                'started_at' => null,
                'completed_at' => null,
                'notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->toArray();

            // Stage progress will be assigned when trainer is assigned
            // For now we store them keyed to be associated later

            $this->activityLogger->log(
                ActivityLogger::REQUEST_CREATED,
                "Onboarding request {$request->request_code} created",
                ['request_id' => $request->id]
            );

            return $request;
        });
    }

    public function assignTrainer(OnboardingRequest $request, string $trainerId, ?string $notes, string $assignedById): TrainingAssignment
    {
        $this->statusManager->validateTransition($request, 'assigned');

        return DB::transaction(function () use ($request, $trainerId, $notes, $assignedById) {
            $assignment = TrainingAssignment::create([
                'onboarding_request_id' => $request->id,
                'trainer_id' => $trainerId,
                'assigned_by_user_id' => $assignedById,
                'assigned_at' => now(),
                'status' => 'assigned',
                'notes' => $notes,
            ]);

            $request->update(['status' => 'assigned']);

            // Bulk-insert stage_progress for all active stages of the request's system
            $stages = OnboardingStage::where('system_id', $request->system_id)
                ->where('is_active', true)
                ->orderBy('sequence_order')
                ->get();

            $now = now();
            $rows = $stages->map(fn ($stage) => [
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'assignment_id' => $assignment->id,
                'stage_id' => $stage->id,
                'status' => 'not_started',
                'progress_percentage' => 0.00,
                'started_at' => null,
                'completed_at' => null,
                'notes' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ])->toArray();

            if (! empty($rows)) {
                StageProgress::insert($rows);
            }

            $this->notificationService->notify(
                [$trainerId],
                'assignment_created',
                'New Training Assignment',
                "You have been assigned to onboarding request {$request->request_code}",
                ['type' => 'training_assignment', 'id' => $assignment->id]
            );

            $this->activityLogger->log(
                ActivityLogger::TRAINER_ASSIGNED,
                "Trainer assigned to request {$request->request_code}",
                ['request_id' => $request->id, 'assignment_id' => $assignment->id]
            );

            return $assignment;
        });
    }

    public function cancelRequest(OnboardingRequest $request, ?string $reason = null): void
    {
        $this->statusManager->validateTransition($request, 'cancelled');

        DB::transaction(function () use ($request, $reason) {
            // 1. Cancel all future scheduled sessions
            $sessionIds = TrainingSession::whereHas('assignment', fn ($q) => $q->where('onboarding_request_id', $request->id))
                ->where('status', 'scheduled')
                ->pluck('id');

            TrainingSession::whereIn('id', $sessionIds)->update(['status' => 'cancelled']);

            // 2. Cancel invited/confirmed attendees for those sessions
            if ($sessionIds->isNotEmpty()) {
                \App\Models\SessionAttendee::whereIn('session_id', $sessionIds)
                    ->whereIn('attendance_status', ['invited', 'confirmed'])
                    ->update(['attendance_status' => 'cancelled']);
            }

            // 3. Mark active assignment as completed (early termination)
            $activeAssignment = $request->assignments()
                ->whereNotIn('status', ['completed', 'rejected'])
                ->first();

            if ($activeAssignment) {
                $activeAssignment->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                $this->notificationService->notify(
                    [$activeAssignment->trainer_id],
                    'session_cancelled',
                    'Training Cancelled',
                    "Onboarding request {$request->request_code} has been cancelled. Reason: ".($reason ?? 'N/A')
                );
            }

            // 4. Cancel request
            $request->update([
                'status' => 'cancelled',
                'actual_end_date' => now()->toDateString(),
            ]);

            $this->activityLogger->log(
                ActivityLogger::REQUEST_CANCELLED,
                "Request {$request->request_code} cancelled. Reason: ".($reason ?? 'N/A'),
                ['request_id' => $request->id]
            );
        });
    }

    public function getDashboard(User $user): array
    {
        $role = $user->role->role ?? null;

        $query = OnboardingRequest::with([
            'client',
            'system',
            'assignments' => fn ($q) => $q->with(['stageProgress']),
        ]);

        if ($role === 'sale') {
            $query->where('created_by_user_id', $user->id);
        }

        $requests = $query->orderByDesc('created_at')->get();

        return $requests->map(function ($req) {
            $assignment = $req->assignments->sortByDesc('created_at')->first();

            return [
                'id' => $req->id,
                'request_code' => $req->request_code,
                'client' => $req->client?->company_name,
                'system' => $req->system?->name,
                'status' => $req->status,
                'priority' => $req->priority,
                'session_count' => $assignment?->sessions()->count() ?? 0,
                'overall_progress' => $assignment
                    ? $this->statusManager->calculateOverallProgress($assignment)
                    : 0.0,
                'created_at' => $req->created_at,
            ];
        })->values()->toArray();
    }

    public function listRequests(string $role, string $userId, array $filters = []): \Illuminate\Support\Collection
    {
        $query = OnboardingRequest::with(['client', 'system', 'createdBy']);

        if ($role === 'sale') {
            $query->where('created_by_user_id', $userId);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        return $query->orderByDesc('created_at')->get();
    }

    public function getRequest(string $requestId): OnboardingRequest
    {
        return OnboardingRequest::with(['client', 'system', 'createdBy', 'assignments.trainer'])
            ->findOrFail($requestId);
    }

    public function listStages(string $systemId, bool $includeInactive = false): Collection
    {
        return OnboardingStage::where('system_id', $systemId)
            ->when(! $includeInactive, fn ($q) => $q->where('is_active', true))
            ->with('system')
            ->orderBy('sequence_order')
            ->get();
    }

    public function getStage(string $stageId): OnboardingStage
    {
        return OnboardingStage::with('system')->findOrFail($stageId);
    }

    public function createStage(array $data): array
    {
        $stage = OnboardingStage::create($data);

        $hasAssignedTrainers = TrainingAssignment::hasActiveAssignmentForSystem(
            $stage->system_id
        );


        $this->activityLogger->log(
            ActivityLogger::STAGE_CREATED,
            "Onboarding stage '{$stage->name}' created",
            ['stage_id' => $stage->id]
        );

        return [
            'stage' => $stage->fresh(),
            'has_assigned_trainers' => $hasAssignedTrainers,
        ];
    }

    public function updateStage(OnboardingStage $stage, array $data): OnboardingStage
    {
        $stage->update(array_filter($data, fn ($v) => ! is_null($v)));

        $this->activityLogger->log(
            ActivityLogger::STAGE_UPDATED,
            "Onboarding stage '{$stage->name}' updated",
            ['stage_id' => $stage->id]
        );

        return $stage->fresh();
    }

    public function toggleStageActive(OnboardingStage $stage): OnboardingStage
    {
        $stage->update(['is_active' => ! $stage->is_active]);

        $this->activityLogger->log(
            ActivityLogger::STAGE_UPDATED,
            "Onboarding stage '{$stage->name}' toggled is_active to ".($stage->is_active ? 'true' : 'false'),
            ['stage_id' => $stage->id]
        );

        return $stage->fresh();
    }
}
