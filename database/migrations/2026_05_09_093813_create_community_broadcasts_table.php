<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('community_broadcasts', function (Blueprint $table) {
            $table->id('community_broadcast_id');

            // Foreign Keys
            $table->foreignId('alert_id')->constrained('alerts', 'alert_id')->onDelete('cascade');
            $table->foreignId('community_id')->constrained('communities', 'community_id')->onDelete('cascade');

            // Metadata for the Delivery Auditor submodule
            $table->string('community_status'); // e.g., 'success', 'failed'
            $table->string('telegram_message_id')->nullable(); // From Telegram API response
            $table->text('error_log')->nullable(); // To store "Bot was kicked" etc.

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_broadcasts');
    }
};
