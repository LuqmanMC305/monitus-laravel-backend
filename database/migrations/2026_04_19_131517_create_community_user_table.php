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
        Schema::create('community_user', function (Blueprint $table) {
            $table->id('community_user_id');

            $table->foreignID('mobile_user_id')->constrained('mobile_users', 'mobile_user_id')->onDelete('cascade');
            $table->foreignID('community_id')->constrained('communities', 'community_id')->onDelete('cascade');

            // Attributes for Role and Audit
            $table->string('role')->default('member');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_user');
    }
};
