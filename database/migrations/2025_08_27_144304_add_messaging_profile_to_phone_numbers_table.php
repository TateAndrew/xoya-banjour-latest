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
        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->foreignId('messaging_profile_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamp('assigned_to_profile_at')->nullable();
            
            $table->index(['messaging_profile_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->dropForeign(['messaging_profile_id']);
            $table->dropColumn(['messaging_profile_id', 'assigned_to_profile_at']);
        });
    }
};
