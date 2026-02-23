<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_sessions', function (Blueprint $table) {
            $table->uuid('start_proof_media_id')->nullable()->after('completion_notes');
            $table->uuid('end_proof_media_id')->nullable()->after('start_proof_media_id');
            $table->integer('student_count')->nullable()->after('end_proof_media_id');
            $table->text('cancellation_reason')->nullable()->after('student_count');
            $table->uuid('cancelled_by_user_id')->nullable()->after('cancellation_reason');
            $table->timestamp('cancelled_at')->nullable()->after('cancelled_by_user_id');
            $table->text('reschedule_reason')->nullable()->after('cancelled_at');

            $table->foreign('start_proof_media_id')->references('id')->on('media')->nullOnDelete();
            $table->foreign('end_proof_media_id')->references('id')->on('media')->nullOnDelete();
            $table->foreign('cancelled_by_user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('training_sessions', function (Blueprint $table) {
            $table->dropForeign(['start_proof_media_id']);
            $table->dropForeign(['end_proof_media_id']);
            $table->dropForeign(['cancelled_by_user_id']);
            $table->dropColumn([
                'start_proof_media_id',
                'end_proof_media_id',
                'student_count',
                'cancellation_reason',
                'cancelled_by_user_id',
                'cancelled_at',
                'reschedule_reason',
            ]);
        });
    }
};
