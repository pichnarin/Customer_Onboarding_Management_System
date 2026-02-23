<?php

namespace App\Providers;

use App\Services\Logging\ActivityLogger;
use App\Services\Notification\NotificationService;
use App\Services\Notification\TelegramService;
use App\Services\Onboarding\OnboardingService;
use App\Services\Onboarding\StatusManager;
use App\Services\Onboarding\TrainingService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ActivityLogger::class);
        $this->app->singleton(StatusManager::class);
        $this->app->singleton(NotificationService::class);
        $this->app->singleton(TelegramService::class);
        $this->app->singleton(OnboardingService::class);
        $this->app->singleton(TrainingService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
