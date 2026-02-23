<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateStageRequest;
use App\Http\Requests\UpdateStageRequest;
use App\Models\OnboardingStage;
use App\Services\Onboarding\OnboardingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingStageController extends Controller
{
    public function __construct(
        private OnboardingService $onboardingService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $systemId = $request->query('system_id');
        $includeInactive = filter_var($request->query('include_inactive', false), FILTER_VALIDATE_BOOLEAN);

        if (! $systemId) {
            return response()->json([
                'success' => false,
                'message' => 'system_id query parameter is required',
            ], 422);
        }

        $stages = $this->onboardingService->listStages($systemId, $includeInactive);

        return response()->json([
            'success' => true,
            'data' => $stages,
        ]);
    }

    public function show(string $id): JsonResponse
    {
        $stage = $this->onboardingService->getStage($id);

        return response()->json([
            'success' => true,
            'data' => $stage,
        ]);
    }

    public function store(CreateStageRequest $request): JsonResponse
    {
        if (! in_array($request->get('auth_role'), ['admin', 'sale'])) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        $stage = $this->onboardingService->createStage($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Stage created successfully',
            'data' => $stage['stage'],
            'meta' => [
                'show_assignment_notice' => $stage['has_assigned_trainers'],
                'notice_message' => $stage['has_assigned_trainers']
                    ? 'This system already has assigned trainers. The new stage will not be applied to existing training assignments.'
                    : null,
            ],
        ], 201);
    }

    public function update(UpdateStageRequest $request, string $id): JsonResponse
    {
        if (! in_array($request->get('auth_role'), ['admin', 'sale'])) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        $stage = OnboardingStage::findOrFail($id);
        $updated = $this->onboardingService->updateStage($stage, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Stage updated successfully',
            'data' => $updated,
        ]);
    }

    public function toggle(Request $request, string $id): JsonResponse
    {
        if (! in_array($request->get('auth_role'), ['admin', 'sale'])) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden',
            ], 403);
        }

        $stage = OnboardingStage::findOrFail($id);
        $updated = $this->onboardingService->toggleStageActive($stage);

        return response()->json([
            'success' => true,
            'message' => 'Stage toggled successfully',
            'data' => ['is_active' => $updated->is_active],
        ]);
    }
}
