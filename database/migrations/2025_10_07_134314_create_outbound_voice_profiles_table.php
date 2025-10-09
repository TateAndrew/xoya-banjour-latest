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
        Schema::create('outbound_voice_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('telnyx_profile_id')->unique();
            $table->string('name');
            $table->boolean('traffic_type')->default(false); // false = conversational, true = short_duration
            $table->string('service_plan')->default('global'); // global, metered, or other plans
            $table->integer('concurrent_call_limit')->nullable();
            $table->boolean('enabled')->default(true);
            $table->string('tags')->nullable();
            $table->integer('max_destination_rate')->nullable();
            $table->integer('daily_spend_limit')->nullable();
            $table->string('daily_spend_limit_enabled')->default('disabled');
            $table->string('call_recording_type')->nullable(); // 'all', 'none', or null
            $table->string('call_recording_caller_phone_numbers')->nullable();
            $table->string('call_recording_callee_phone_numbers')->nullable();
            $table->integer('call_recording_channels')->nullable(); // 'single' or 'dual'
            $table->string('call_recording_format')->nullable(); // 'wav' or 'mp3'
            $table->text('billing_group_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outbound_voice_profiles');
    }
};
