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
        Schema::create('call_transcripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_id')->constrained()->onDelete('cascade');
            $table->string('call_control_id'); // Telnyx call control ID
            $table->string('transcription_id')->nullable(); // Telnyx transcription ID
            $table->enum('status', ['pending', 'started', 'processing', 'completed', 'failed'])->default('pending');
            $table->string('language')->default('en-US'); // Language for transcription
            $table->text('transcript_text')->nullable(); // Full transcript text
            $table->json('transcript_data')->nullable(); // Detailed transcript with timestamps
            $table->timestamp('started_at')->nullable(); // When transcription started
            $table->timestamp('completed_at')->nullable(); // When transcription completed
            $table->integer('duration')->nullable(); // Duration in seconds
            $table->json('metadata')->nullable(); // Additional metadata
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['call_id']);
            $table->index(['call_control_id']);
            $table->index(['transcription_id']);
            $table->index(['status']);
            $table->index(['started_at']);
            $table->unique(['call_control_id']); // One transcription per call
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_transcripts');
    }
};