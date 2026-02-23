<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddStudentRequest;
use App\Http\Requests\AppointmentRequest;
use App\Http\Requests\CancelSessionRequest;
use App\Http\Requests\CompleteSessionRequest;
use App\Http\Requests\MarkAttendanceRequest;
use App\Http\Requests\RejectAssignmentRequest;
use App\Http\Requests\RescheduleSessionRequest;
use App\Http\Requests\SkipStageRequest;
use App\Http\Requests\StartSessionRequest;
use App\Models\SessionAttendee;
use App\Models\SessionStudent;
use App\Models\StageProgress;
use App\Models\TrainingAssignment;
use App\Models\TrainingSession;
use App\Models\User;
use App\Services\Onboarding\TrainingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppointmentController extends Controller
{
    public function __construct(
        private TrainingService $trainingService
    ) {}

    public function listAssignments(Request $request): JsonResponse
    {
        $user = User::findOrFail($request->get('auth_user_id'));
        $assignments = $this->trainingService->listAssignments($user);

        return response()->json([
            'success' => true,
            'data' => $assignments,
        ]);
    }

    public function getAssignment(string $id): JsonResponse
    {
        $assignment = $this->trainingService->getAssignment($id);

        return response()->json([
            'success' => true,
            'data' => $assignment,
        ]);
    }

    public function acceptAssignment(Request $request, string $id): JsonResponse
    {
        $assignment = TrainingAssignment::findOrFail($id);

        $this->trainingService->acceptAssignment($assignment);

        return response()->json([
            'success' => true,
            'message' => 'Assignment accepted',
            'data' => ['status' => $assignment->fresh()->status],
        ]);
    }

    public function rejectAssignment(RejectAssignmentRequest $request, string $id): JsonResponse
    {
        $assignment = TrainingAssignment::findOrFail($id);

        $this->trainingService->rejectAssignment($assignment, $request->input('rejection_reason'));

        return response()->json([
            'success' => true,
            'message' => 'Assignment rejected',
        ]);
    }

    public function createSession(AppointmentRequest $request, string $assignmentId): JsonResponse
    {
        $assignment = TrainingAssignment::findOrFail($assignmentId);

        $session = $this->trainingService->createSession($assignment, $request->validated(), $request->get('auth_user_id'));

        return response()->json([
            'success' => true,
            'message' => 'Session created successfully',
            'data' => [
                'session_id' => $session->id,
                'session_title' => $session->session_title,
                'status' => $session->status,
                'creator_id' => $session->creator_id,
            ],
        ], 201);
    }

    public function listSessions(string $assignmentId): JsonResponse
    {
        $sessions = $this->trainingService->listSessions($assignmentId);

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    public function startSession(StartSessionRequest $request, string $id): JsonResponse
    {
        $session = TrainingSession::findOrFail($id);

        $this->trainingService->startSession(
            $session,
            $request->input('start_proof_media_id'),
            (float) $request->input('start_latitude'),
            (float) $request->input('start_longitude')
        );

        return response()->json([
            'success' => true,
            'message' => 'Session started',
            'data' => ['status' => $session->fresh()->status],
        ]);
    }

    public function completeSession(CompleteSessionRequest $request, string $id): JsonResponse
    {
        $session = TrainingSession::findOrFail($id);

        $this->trainingService->completeSession(
            $session,
            $request->input('completion_notes'),
            $request->input('end_proof_media_id'),
            (int) $request->input('student_count'),
            (float) $request->input('end_latitude'),
            (float) $request->input('end_longitude')
        );

        return response()->json([
            'success' => true,
            'message' => 'Session completed',
            'data' => ['status' => $session->fresh()->status],
        ]);
    }

    public function rescheduleSession(RescheduleSessionRequest $request, string $id): JsonResponse
    {
        $session = TrainingSession::findOrFail($id);

        $newSession = $this->trainingService->rescheduleSession($session, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Session rescheduled',
            'data' => [
                'new_session_id' => $newSession->id,
                'status' => $newSession->status,
            ],
        ]);
    }

    public function cancelSession(CancelSessionRequest $request, string $id): JsonResponse
    {
        $session = TrainingSession::findOrFail($id);

        $this->trainingService->cancelSession(
            $session,
            $request->input('cancellation_reason'),
            $request->get('auth_user_id')
        );

        return response()->json([
            'success' => true,
            'message' => 'Session cancelled',
        ]);
    }

    public function markAttendance(MarkAttendanceRequest $request, string $sessionId, string $attendeeId): JsonResponse
    {
        $attendee = SessionAttendee::where('session_id', $sessionId)
            ->where('id', $attendeeId)
            ->firstOrFail();

        $this->trainingService->markAttendance(
            $attendee,
            $request->input('attendance_status'),
            $request->input('notes')
        );

        return response()->json([
            'success' => true,
            'message' => 'Attendance marked',
            'data' => ['attendance_status' => $attendee->fresh()->attendance_status],
        ]);
    }

    public function addStudents(AddStudentRequest $request, string $id): JsonResponse
    {
        $session = TrainingSession::findOrFail($id);

        $this->trainingService->addStudents($session, $request->input('students'));

        return response()->json([
            'success' => true,
            'message' => 'Students added successfully',
            'data' => ['count' => count($request->input('students'))],
        ], 201);
    }

    public function listStudents(string $id): JsonResponse
    {
        $students = SessionStudent::where('session_id', $id)->get();

        return response()->json([
            'success' => true,
            'data' => $students,
        ]);
    }

    public function skipStage(SkipStageRequest $request, string $progressId): JsonResponse
    {
        $progress = StageProgress::findOrFail($progressId);

        $this->trainingService->skipStage($progress, $request->input('notes'));

        return response()->json([
            'success' => true,
            'message' => 'Stage skipped',
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $user = User::findOrFail($request->get('auth_user_id'));

        $data = $this->trainingService->getTrainerDashboard($user);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
