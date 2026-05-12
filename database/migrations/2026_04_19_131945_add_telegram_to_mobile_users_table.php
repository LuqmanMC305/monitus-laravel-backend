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
        Schema::table('mobile_users', function (Blueprint $table) {
            $table->bigInteger('telegram_chat_id')->unique()->nullable()->after('last_location_at');
            $table->boolean('is_telegram_verified')->default(false)->after('telegram_chat_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mobile_users', function (Blueprint $table) {
            //
        });
    }
};
