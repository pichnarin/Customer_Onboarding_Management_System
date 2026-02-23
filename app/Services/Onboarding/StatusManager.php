<?php

namespace App\Services\Onboarding;

use App\Exceptions\Business\InvalidStatusTransitionException;
use App\Models\OnboardingRequest;
use App\Models\StageProgress;
use App\Models\TrainingAssignment;
use App\Models\TrainingSession;
use Illuminate\Database\Eloquent\Model;

class StatusManager
{
    private array $allowedTransitions = [
        OnboardingRequest::class => [
            'pending' => ['assigned', 'cancelled'],
            'assigned' => ['in_progress', 'cancelled', 'pending'],
            'in_progress' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ],
        TrainingAssignment::class => [
            'assigned' => ['accepted', 'rejected'],
            'accepted' => ['in_progress'],
            'in_progress' => ['completed'],
            'completed' => [],
            'rejected' => [],
        ],
        TrainingSession::class => [
            'scheduled' => ['in_progress', 'cancelled', 'rescheduled'],
            'in_progress' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
            'rescheduled' => [],
        ],
        StageProgress::class => [
            'not_started' => ['in_progress', 'skipped'],
            'in_progress' => ['completed', 'skipped'],
            'completed' => [],
            'skipped' => [],
        ],
    ];

    public function canTransition(Model $model, string $toStatus): bool
    {
        $class = get_class($model);
        $fromStatus = $model->status;
        $transitions = $this->allowedTransitions[$class] ?? [];

        return in_array($toStatus, $transitions[$fromStatus] ?? [], true);
    }

    public function validateTransition(Model $model, string $toStatus): void
    {
        if (! $this->canTransition($model, $toStatus)) {
            $class = class_basename($model);
            throw new InvalidStatusTransitionException(
                "Cannot transition {$class} from '{$model->status}' to '{$toStatus}'"
            );
        }
    }

    public function cascadeOnSessionComplete(TrainingSession $session): void
    {
        $assignment = $session->assignment;
        $stageId = $session->stage_id;

        // Update stage progress percentage
        $percentage = $this->calculateStageProgress($assignment->id, $stageId);

        $stageProgress = StageProgress::where('assignment_id', $assignment->id)
            ->where('stage_id', $stageId)
            ->first();

        if (! $stageProgress) {
            return;
        }

        $stageProgress->update(['progress_percentage' => $percentage]);

        // Check if all sessions in stage are completed
        $totalSessions = TrainingSession::where('assignment_id', $assignment->id)
            ->where('stage_id', $stageId)
            ->whereNotIn('status', ['cancelled', 'rescheduled'])
            ->count();

        $completedSessions = TrainingSession::where('assignment_id', $assignment->id)
            ->where('stage_id', $stageId)
            ->where('status', 'completed')
            ->count();

        if ($totalSessions > 0 && $completedSessions >= $totalSessions) {
            $stageProgress->update([
                'status' => 'completed',
                'progress_percentage' => 100.00,
                'completed_at' => now(),
            ]);
        }

        // Re-fetch to check latest status
        $stageProgress->refresh();

        // Check if all stages are done (completed or skipped)
        $allStagesDone = StageProgress::where('assignment_id', $assignment->id)
            ->whereNotIn('status', ['completed', 'skipped'])
            ->doesntExist();

        if ($allStagesDone && $assignment->status === 'in_progress') {
            $assignment->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Check if request should be completed
            $request = $assignment->onboardingRequest;
            if ($request && $request->status === 'in_progress') {
                $request->update([
                    'status' => 'completed',
                    'actual_end_date' => now()->toDateString(),
                ]);
            }
        }
    }

    public function calculateStageProgress(string $assignmentId, string $stageId): float
    {
        $total = TrainingSession::where('assignment_id', $assignmentId)
            ->where('stage_id', $stageId)
            ->whereNotIn('status', ['cancelled', 'rescheduled'])
            ->count();

        if ($total === 0) {
            return 0.0;
        }

        $completed = TrainingSession::where('assignment_id', $assignmentId)
            ->where('stage_id', $stageId)
            ->where('status', 'completed')
            ->count();

        return round(($completed / $total) * 100, 2);
    }

    public function calculateOverallProgress(TrainingAssignment $assignment): float
    {
        $avg = StageProgress::where('assignment_id', $assignment->id)
            ->avg('progress_percentage');

        return round((float) ($avg ?? 0.0), 2);
    }

    public function generateRequestCode(): string
    {
        $year = now()->year;

        $latest = OnboardingRequest::whereYear('created_at', $year)
            ->orderByDesc('request_code')
            ->value('request_code');

        if ($latest) {
            $parts = explode('-', $latest);
            $number = (int) end($parts) + 1;
        } else {
            $number = 1;
        }

        return sprintf('REQ-%d-%04d', $year, $number);
    }
}
