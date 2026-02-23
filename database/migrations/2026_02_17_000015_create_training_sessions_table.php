<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assignment_id');
            $table->uuid('stage_id');
            $table->string('session_title', 255);
            $table->text('session_description')->nullable();
            $table->date('scheduled_date');
            $table->time('scheduled_start_time');
            $table->time('scheduled_end_time');
            $table->dateTime('actual_start_time')->nullable();
            $table->dateTime('actual_end_time')->nullable();
            $table->enum('location_type', ['online', 'onsite', 'hybrid'])->default('online');
            $table->string('meeting_link', 500)->nullable();
            $table->text('physical_location')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled', 'rescheduled'])->default('scheduled');
            $table->text('completion_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('assignment_id')
                ->references('id')
                ->on('training_assignments')
                ->onDelete('cascade');

            $table->foreign('stage_id')
                ->references('id')
                ->on('onboarding_stages')
                ->onDelete('restrict');

            $table->index('assignment_id');
            $table->index('stage_id');
            $table->index('scheduled_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_sessions');
    }
};
