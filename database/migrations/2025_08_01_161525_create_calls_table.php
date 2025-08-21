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
            $table->enum('call_type', ['voice', 'video', 'conference'])->default('voice');
            $table->enum('status', ['initiating', 'ringing', 'answered', 'in_progress', 'ended', 'failed'])->default('initiating');
            $table->string('telnyx_call_id')->nullable();
            $table->string('sip_trunk_id')->nullable();
            $table->string('conference_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration')->nullable(); // in seconds
            $table->decimal('cost', 10, 4)->nullable();
            $table->string('recording_url')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['status', 'created_at']);
            $table->index('telnyx_call_id');
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
