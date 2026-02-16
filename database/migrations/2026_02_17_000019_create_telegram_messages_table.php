<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('client_contact_id');
            $table->string('message_type', 50);
            $table->text('message_content');
            $table->string('telegram_message_id', 255)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->enum('delivery_status', ['pending', 'sent', 'delivered', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->uuid('related_session_id')->nullable();
            $table->timestamps();

            $table->foreign('client_contact_id')
                  ->references('id')
                  ->on('client_contacts')
                  ->onDelete('cascade');

            $table->foreign('related_session_id')
                  ->references('id')
                  ->on('training_sessions')
                  ->onDelete('set null');

            $table->index('client_contact_id');
            $table->index('sent_at');
            $table->index('delivery_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_messages');
    }
};
