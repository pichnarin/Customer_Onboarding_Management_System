<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleOAuthController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OnBoardingController;
use App\Http\Controllers\OnboardingStageController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redis;


Route::get('/healthcheck', function () {
    $status = [
        'status' => 'OK',
        'php_version' => phpversion(),
        'database' => ['status' => 'unknown'],
        'redis' => ['status' => 'unknown'],
    ];

    $httpCode = 200;

    try {
        DB::connection()->getPdo();
        $dbName = DB::connection()->getDatabaseName();
        $status['database'] = [
            'status' => 'connected',
            'database' => $dbName,
        ];
    } catch (\Exception $e) {
        $status['database'] = [
            'status' => 'error',
            'message' => 'Database connection failed',
        ];
        $httpCode = 500;
    }

    try {
        Redis::ping();
        $status['redis'] = [
            'status' => 'connected',
        ];
    } catch (\Exception $e) {
        $status['redis'] = [
            'status' => 'error',
            'message' => 'Redis connection failed',
        ];
        $httpCode = 500;
    }

    if ($httpCode === 500) {
        $status['status'] = 'ERROR';
        $status['message'] = 'Some services are down';
    } else {
        $status['status'] = 'OK';
        $status['message'] = 'Customer Onboarding Management API is running smoothly.';
    }

    return response()->json($status, $httpCode);
});

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
});

Route::get('/google/callback', [GoogleOAuthController::class, 'callback']);

Route::middleware(['jwt.auth'])->group(function () {

    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/get-profile', [UserController::class, 'getProfile']);

    Route::get('/google/redirect', [GoogleOAuthController::class, 'redirect']);
    Route::get('/google/status', [GoogleOAuthController::class, 'status']);
    Route::delete('/google/disconnect', [GoogleOAuthController::class, 'disconnect']);

    Route::middleware(['role:admin'])->group(function () {
        Route::get('/user-detail/{userId}', [UserController::class, 'getUserById']);
        Route::get('/get-users', [UserController::class, 'listUsers']);
        Route::post('/create-user', [UserController::class, 'createUser']);
        Route::patch('/update-user-information/{userId}', [UserController::class, 'updateUserInformation']);
        Route::delete('/soft-delete-user/{userId}', [UserController::class, 'softDeleteUser']);
        Route::delete('/hard-delete-user/{userId}', [UserController::class, 'hardDeleteUser']);
        Route::patch('/restore-user/{userId}', [UserController::class, 'restoreUser']);
    });

    Route::middleware(['role:admin,sale'])->group(function () {
        Route::prefix('selection')->group(function () {
           Route::get('/trainers-dropdown', [UserController::class, 'listTrainers']);
           Route::get('/clients-dropdown', [UserController::class, 'listClients']);
           Route::get('/systems-dropdown', [SystemController::class, 'listSystems']);
        });

        Route::prefix('onboarding')->group(function () {
            Route::post('/requests', [OnBoardingController::class, 'createRequest']);
            Route::get('/requests', [OnBoardingController::class, 'listRequests']);
            Route::get('/requests/{id}', [OnBoardingController::class, 'getRequest']);
            Route::patch('/requests/{id}/assign-trainer', [OnBoardingController::class, 'assignTrainer']);
            Route::patch('/requests/{id}/cancel', [OnBoardingController::class, 'cancelRequest']);
            Route::get('/dashboard', [OnBoardingController::class, 'dashboard']);

            // Onboarding stages (all roles read, admin+sale write)
            Route::get('/stages', [OnboardingStageController::class, 'index']);
            Route::get('/stages/{id}', [OnboardingStageController::class, 'show']);
            Route::post('/stages', [OnboardingStageController::class, 'store']);
            Route::patch('/stages/{id}', [OnboardingStageController::class, 'update']);
            Route::patch('/stages/{id}/toggle', [OnboardingStageController::class, 'toggle']);
        });
    });

    Route::middleware(['role:sale,trainer'])->group(function () {
        Route::get('/trainer/dashboard', [AppointmentController::class, 'dashboard']);
        Route::patch('/stage-progress/{id}/skip', [AppointmentController::class, 'skipStage']);
        Route::post('/media', [MediaController::class, 'upload']);

        Route::prefix('assignments')->group(function () {
            Route::get('/', [AppointmentController::class, 'listAssignments']);
            Route::get('/{id}', [AppointmentController::class, 'getAssignment']);
            Route::patch('/{id}/accept', [AppointmentController::class, 'acceptAssignment']);
            Route::patch('/{id}/reject', [AppointmentController::class, 'rejectAssignment']);
            Route::post('/{id}/sessions', [AppointmentController::class, 'createSession']);
            Route::get('/{assignmentId}/sessions', [AppointmentController::class, 'listSessions']);
        });

        Route::prefix('sessions')->group(function () {
             Route::patch('/{id}/start', [AppointmentController::class, 'startSession']);
            Route::patch('/{id}/complete', [AppointmentController::class, 'completeSession']);
            Route::patch('/{id}/reschedule', [AppointmentController::class, 'rescheduleSession']);
            Route::patch('/{id}/cancel', [AppointmentController::class, 'cancelSession']);
            Route::patch('/{sessionId}/attendees/{attendeeId}', [AppointmentController::class, 'markAttendance']);
            Route::post('/{id}/students', [AppointmentController::class, 'addStudents']);
            Route::get('/{id}/students', [AppointmentController::class, 'listStudents']);
        });
    });

    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
        Route::patch('/{id}/read', [NotificationController::class, 'markRead']);
        Route::patch('/read-all', [NotificationController::class, 'markAllRead']);
    });

});
