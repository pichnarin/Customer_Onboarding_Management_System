<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\GoogleOAuthController;

Route::get('/health', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'Employee Management API is running smoothly.'
    ]);
});

Route::get('/debug-db', function () {
    try {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        // Test database connection
        DB::connection()->getPdo();
        $dbName = DB::connection()->getDatabaseName();

        // Count users
        $userCount = DB::table('users')->count();
        $credentialCount = DB::table('credentials')->count();

        return response()->json([
            'status' => 'connected',
            'connection' => $connection,
            'database' => $dbName,
            'host' => $config['host'] ?? 'N/A',
            'port' => $config['port'] ?? 'N/A',
            'username' => $config['username'] ?? 'N/A',
            'driver' => $config['driver'] ?? 'N/A',
            'counts' => [
                'users' => $userCount,
                'credentials' => $credentialCount,
            ],
            'env_check' => [
                'DB_CONNECTION' => env('DB_CONNECTION'),
                'DB_HOST' => env('DB_HOST'),
                'DB_PORT' => env('DB_PORT'),
                'DB_DATABASE' => env('DB_DATABASE'),
                'DB_USERNAME' => env('DB_USERNAME'),
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
            'env_check' => [
                'DB_CONNECTION' => env('DB_CONNECTION'),
                'DB_HOST' => env('DB_HOST'),
                'DB_PORT' => env('DB_PORT'),
                'DB_DATABASE' => env('DB_DATABASE'),
            ]
        ], 500);
    }
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

    Route::middleware(['admin.only'])->group(function () {
        Route::get('/user-detail/{userId}', [UserController::class, 'getUserById']);
        Route::get('/get-users', [UserController::class, 'listUsers']);
        Route::post('/create-user', [UserController::class, 'createUser']);
        Route::patch('/update-user-information/{userId}', [UserController::class, 'updateUserInformation']);
        Route::delete('/soft-delete-user/{userId}', [UserController::class, 'softDeleteUser']);
        Route::delete('/hard-delete-user/{userId}', [UserController::class, 'hardDeleteUser']);
        Route::patch('/restore-user/{userId}', [UserController::class, 'restoreUser']);
    });
});
