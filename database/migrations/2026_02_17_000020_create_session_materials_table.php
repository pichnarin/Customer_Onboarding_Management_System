<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_materials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('session_id');
            $table->uuid('media_id');
            $table->enum('material_type', ['presentation', 'handout', 'recording', 'other'])->default('other');
            $table->text('description')->nullable();
            $table->timestamp('uploaded_at')->useCurrent();

            $table->foreign('session_id')
                  ->references('id')
                  ->on('training_sessions')
                  ->onDelete('cascade');

            $table->foreign('media_id')
                  ->references('id')
                  ->on('media')
                  ->onDelete('cascade');

            $table->index('session_id');
            $table->index('media_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_materials');
    }
};
