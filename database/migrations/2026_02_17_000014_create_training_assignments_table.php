<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('onboarding_request_id');
            $table->uuid('trainer_id');
            $table->uuid('assigned_by_user_id');
            $table->timestamp('assigned_at')->useCurrent();
            $table->enum('status', ['assigned', 'accepted', 'in_progress', 'completed', 'rejected'])->default('assigned');
            $table->text('notes')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->softDeletes();

            $table->foreign('onboarding_request_id')
                ->references('id')
                ->on('onboarding_requests')
                ->onDelete('cascade');

            $table->foreign('trainer_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->foreign('assigned_by_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->index('onboarding_request_id');
            $table->index('trainer_id');
            $table->index('assigned_by_user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_assignments');
    }
};
