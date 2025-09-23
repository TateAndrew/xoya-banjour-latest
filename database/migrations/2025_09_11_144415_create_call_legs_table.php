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
        Schema::create('call_legs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_session_id')->constrained()->onDelete('cascade');
            $table->string('call_leg_id')->nullable(); // Telnyx leg ID
            $table->string('call_control_id')->nullable(); // Telnyx call control ID
            $table->string('event_type'); // call.initiated, call.answered, etc.
            $table->string('event_id')->unique(); // Telnyx event ID
            $table->timestamp('occurred_at');
            $table->enum('direction', ['inbound', 'outbound'])->default('outbound');
            $table->string('calling_party_type')->default('sip');
            $table->string('from_number')->nullable();
            $table->string('to_number')->nullable();
            $table->string('from_sip_uri')->nullable();
            $table->string('to_sip_uri')->nullable();
            $table->string('state')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->string('hangup_cause')->nullable();
            $table->string('hangup_source')->nullable();
            $table->string('sip_hangup_cause')->nullable();
            $table->json('call_quality_stats')->nullable();
            $table->json('custom_headers')->nullable();
            $table->string('client_state')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['call_session_id']);
            $table->index(['event_type']);
            $table->index(['event_id']);
            $table->index(['call_control_id']);
            $table->index(['occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_legs');
    }
};
