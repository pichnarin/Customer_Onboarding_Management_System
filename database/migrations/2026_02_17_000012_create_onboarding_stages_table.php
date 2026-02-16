<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('onboarding_stages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->integer('sequence_order');
            $table->integer('estimated_duration_days')->nullable();
            $table->uuid('system_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('system_id')
                  ->references('id')
                  ->on('systems')
                  ->onDelete('restrict');

            $table->index('system_id');
            $table->index('sequence_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('onboarding_stages');
    }
};
