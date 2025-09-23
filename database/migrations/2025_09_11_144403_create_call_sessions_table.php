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
        Schema::create('call_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('phone_number_id')->nullable()->constrained()->onDelete('set null');
            $table->string('call_session_id')->unique(); // Telnyx session ID
            $table->string('from_number');
            $table->string('to_number');
            $table->string('from_sip_uri')->nullable();
            $table->string('to_sip_uri')->nullable();
            $table->enum('call_type', ['voice', 'video', 'conference'])->default('voice');
            $table->enum('direction', ['inbound', 'outbound'])->default('outbound');
            $table->string('calling_party_type')->default('sip');
            $table->string('connection_id')->nullable();
            $table->foreignId('sip_trunk_id')->nullable()->constrained()->onDelete('set null');
            $table->string('conference_id')->nullable();
            $table->enum('status', ['initiating', 'answered', 'in_progress', 'ended', 'failed'])->default('initiating');
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->integer('duration')->nullable(); // Duration in seconds
            $table->json('custom_headers')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['call_id']);
            $table->index(['user_id', 'status']);
            $table->index(['call_session_id']);
            $table->index(['phone_number_id', 'created_at']);
            $table->index(['conference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_sessions');
    }
};
