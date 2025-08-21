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
        Schema::create('conferences', function (Blueprint $table) {
            $table->id();
            $table->string('conference_id')->unique();
            $table->foreignId('host_id')->constrained('users')->onDelete('cascade');
            $table->string('title')->nullable();
            $table->enum('status', ['active', 'ended', 'scheduled'])->default('active');
            $table->integer('max_participants')->default(10);
            $table->json('settings')->nullable(); // recording, mute, etc.
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
            
            $table->index(['conference_id', 'status']);
            $table->index(['host_id', 'created_at']);
        });

        Schema::create('conference_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conference_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('phone_number')->nullable();
            $table->string('participant_name')->nullable();
            $table->enum('role', ['host', 'participant', 'moderator'])->default('participant');
            $table->enum('status', ['joining', 'active', 'muted', 'left'])->default('joining');
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->json('metadata')->nullable(); // device info, etc.
            $table->timestamps();
            
            $table->index(['conference_id', 'status']);
            $table->index(['user_id', 'joined_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conference_participants');
        Schema::dropIfExists('conferences');
    }
};
