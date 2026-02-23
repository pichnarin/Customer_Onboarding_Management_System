<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignTrainerRequest;
use App\Http\Requests\OnBoardingRequest;
use App\Services\Onboarding\OnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnBoardingController extends Controller
{
    public function __construct(
        private OnboardingService $onboardingService
    ) {}

    public function createRequest(OnBoardingRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by_user_id'] = $request->get('auth_user_id');

        $onboardingRequest = $this->onboardingService->createRequest($data);

        return response()->json([
            'success' => true,
            'message' => 'Onboarding request created successfully',
            'data' => [
                'id' => $onboardingRequest->id,
                'request_code' => $onboardingRequest->request_code,
                'status' => $onboardingRequest->status,
            ],
        ], 201);
    }

    public function listRequests(Request $request): JsonResponse
    {
        $role = $request->get('auth_role');
        $userId = $request->get('auth_user_id');

        $filters = $request->only(['status', 'priority']);

        $requests = $this->onboardingService->listRequests($role, $userId, $filters);

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    public function getRequest(string $id): JsonResponse
    {
        $onboardingRequest = $this->onboardingService->getRequest($id);

        return response()->json([
            'success' => true,
            'data' => $onboardingRequest,
        ]);
    }

    public function assignTrainer(AssignTrainerRequest $request, string $id): JsonResponse
    {
        $onboardingRequest = $this->onboardingService->getRequest($id);

        $assignment = $this->onboardingService->assignTrainer(
            $onboardingRequest,
            $request->input('trainer_id'),
            $request->input('notes'),
            $request->get('auth_user_id')
        );

        return response()->json([
            'success' => true,
            'message' => 'Trainer assigned successfully',
            'data' => [
                'assignment_id' => $assignment->id,
                'trainer_id' => $assignment->trainer_id,
                'status' => $assignment->status,
            ],
        ]);
    }

    public function cancelRequest(Request $request, string $id): JsonResponse
    {
        $onboardingRequest = $this->onboardingService->getRequest($id);

        $this->onboardingService->cancelRequest(
            $onboardingRequest,
            $request->input('reason')
        );

        return response()->json([
            'success' => true,
            'message' => 'Onboarding request cancelled',
        ]);
    }

    public function dashboard(Request $request): JsonResponse
    {
        $user = \App\Models\User::findOrFail($request->get('auth_user_id'));

        $data = $this->onboardingService->getDashboard($user);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
