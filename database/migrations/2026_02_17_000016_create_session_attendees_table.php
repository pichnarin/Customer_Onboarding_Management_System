<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_attendees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('session_id');
            $table->uuid('client_contact_id');
            $table->enum('attendance_status', ['invited', 'confirmed', 'attended', 'absent', 'cancelled'])->default('invited');
            $table->dateTime('attended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('session_id')
                ->references('id')
                ->on('training_sessions')
                ->onDelete('cascade');

            $table->foreign('client_contact_id')
                ->references('id')
                ->on('client_contacts')
                ->onDelete('cascade');

            $table->unique(['session_id', 'client_contact_id']);
            $table->index('session_id');
            $table->index('client_contact_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_attendees');
    }
};
