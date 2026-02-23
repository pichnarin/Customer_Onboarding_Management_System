<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stage_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('assignment_id');
            $table->uuid('stage_id');
            $table->enum('status', ['not_started', 'in_progress', 'completed', 'skipped'])->default('not_started');
            $table->decimal('progress_percentage', 5, 2)->default(0.00);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
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

            $table->unique(['assignment_id', 'stage_id']);
            $table->index('assignment_id');
            $table->index('stage_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_progress');
    }
};
