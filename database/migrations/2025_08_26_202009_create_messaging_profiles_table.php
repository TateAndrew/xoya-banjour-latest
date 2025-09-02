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
        Schema::create('messaging_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('telnyx_profile_id')->unique(); // Telnyx's internal ID
            $table->string('name');
            $table->json('whitelisted_destinations'); // Array of ISO 3166-1 alpha-2 codes
            $table->boolean('enabled')->default(true);
            $table->string('webhook_url')->nullable();
            $table->string('webhook_failover_url')->nullable();
            $table->string('webhook_api_version')->default('2');
            $table->json('number_pool_settings')->nullable();
            $table->json('url_shortener_settings')->nullable();
            $table->string('alpha_sender')->nullable();
            $table->string('daily_spend_limit')->nullable();
            $table->boolean('daily_spend_limit_enabled')->default(false);
            $table->boolean('mms_fall_back_to_sms')->default(false);
            $table->boolean('mms_transcoding')->default(false);
            $table->json('metadata')->nullable(); // Additional Telnyx data
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index('telnyx_profile_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messaging_profiles');
    }
};
