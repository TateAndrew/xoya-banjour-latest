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
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('phone_number_id')->nullable()->constrained()->onDelete('set null');
            $table->string('from_number');
            $table->string('to_number');
            $table->string('from_sip_uri')->nullable();
            $table->string('to_sip_uri')->nullable();
            $table->enum('call_type', ['voice', 'video', 'conference'])->default('voice');
            $table->enum('status', ['initiating', 'answered', 'in_progress', 'ended', 'failed'])->default('initiating');
            $table->string('telnyx_call_id')->nullable();
            $table->string('call_control_id')->nullable();
            $table->string('call_leg_id')->nullable();
            $table->string('call_session_id')->nullable();
            $table->foreignId('sip_trunk_id')->nullable()->constrained()->onDelete('set null');
            $table->string('conference_id')->nullable();
            $table->string('connection_id')->nullable();
            $table->enum('direction', ['incoming', 'outgoing'])->default('outgoing');
            $table->string('calling_party_type')->default('sip');
            $table->string('state')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration')->nullable(); // Duration in seconds
            $table->string('hangup_cause')->nullable();
            $table->string('hangup_source')->nullable();
            $table->string('sip_hangup_cause')->nullable();
            $table->json('call_quality_stats')->nullable();
            $table->json('custom_headers')->nullable();
            $table->string('client_state')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['phone_number_id', 'created_at']);
            $table->index(['call_control_id']);
            $table->index(['call_leg_id']);
            $table->index(['call_session_id']);
            $table->index(['telnyx_call_id']);
            $table->index(['conference_id']);
            $table->index(['connection_id']);
            $table->index(['from_number', 'to_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};
