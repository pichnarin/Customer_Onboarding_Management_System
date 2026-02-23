<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('request_code', 50)->unique();
            $table->uuid('client_id');
            $table->uuid('system_id');
            $table->uuid('created_by_user_id');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['pending', 'assigned', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->date('expected_start_date')->nullable();
            $table->date('expected_end_date')->nullable();
            $table->date('actual_start_date')->nullable();
            $table->date('actual_end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->onDelete('restrict');

            $table->foreign('system_id')
                ->references('id')
                ->on('systems')
                ->onDelete('restrict');

            $table->foreign('created_by_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->index('request_code');
            $table->index('client_id');
            $table->index('created_by_user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_requests');
    }
};
