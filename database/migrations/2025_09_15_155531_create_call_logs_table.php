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
        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_id')->constrained()->onDelete('cascade');
            $table->string('event_type'); // call.initiated, call.answered, etc.
            $table->string('event_id')->unique(); // Telnyx event ID
            $table->timestamp('occurred_at'); // When the event occurred
            $table->string('call_control_id')->nullable();
            $table->string('call_leg_id')->nullable();
            $table->string('call_session_id')->nullable();
            $table->string('connection_id')->nullable();
            $table->string('direction')->nullable(); // incoming/outgoing
            $table->string('calling_party_type')->nullable(); // sip, etc.
            $table->string('state')->nullable(); // bridging, answered, etc.
            $table->string('from_number')->nullable();
            $table->string('to_number')->nullable();
            $table->string('from_sip_uri')->nullable();
            $table->string('to_sip_uri')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->string('hangup_cause')->nullable();
            $table->string('hangup_source')->nullable();
            $table->string('sip_hangup_cause')->nullable();
            $table->json('call_quality_stats')->nullable();
            $table->json('custom_headers')->nullable();
            $table->string('client_state')->nullable();
            $table->json('raw_payload')->nullable(); // Complete webhook payload
            $table->json('meta')->nullable(); // Webhook meta information
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['call_id', 'event_type']);
            $table->index(['event_id']);
            $table->index(['call_control_id']);
            $table->index(['call_leg_id']);
            $table->index(['call_session_id']);
            $table->index(['occurred_at']);
            $table->index(['event_type', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_logs');
    }
};