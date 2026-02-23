<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_sessions', function (Blueprint $table) {
            $table->decimal('start_latitude', 10, 7)->nullable()->after('start_proof_media_id');
            $table->decimal('start_longitude', 10, 7)->nullable()->after('start_latitude');
            $table->decimal('end_latitude', 10, 7)->nullable()->after('end_proof_media_id');
            $table->decimal('end_longitude', 10, 7)->nullable()->after('end_latitude');
        });
    }

    public function down(): void
    {
        Schema::table('training_sessions', function (Blueprint $table) {
            $table->dropColumn(['start_latitude', 'start_longitude', 'end_latitude', 'end_longitude']);
        });
    }
};
