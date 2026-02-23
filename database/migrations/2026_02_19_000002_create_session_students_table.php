<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('session_id');
            $table->string('name', 255)->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->string('profession', 100)->nullable();
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('training_sessions')->cascadeOnDelete();
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_students');
    }
};
