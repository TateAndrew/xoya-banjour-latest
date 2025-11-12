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
        Schema::create('video_calls', function (Blueprint $table) {
            $table->id();
            $table->string('room_name')->unique();
            $table->foreignId('host_user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('participant_user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->onDelete('cascade');
            $table->string('status')->default('scheduled'); // scheduled, active, ended, cancelled
            $table->string('type')->default('one_to_one'); // one_to_one, group, conference
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration')->nullable(); // Duration in seconds
            $table->json('participants')->nullable(); // Array of participant info
            $table->json('metadata')->nullable(); // Additional metadata
            $table->timestamps();
            
            $table->index('room_name');
            $table->index('host_user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_calls');
    }
};
