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
        Schema::create('recordings', function (Blueprint $table) {
            $table->id();
            $table->uuid('telnyx_recording_id')->unique();
            $table->foreignId('call_id')->nullable()->constrained('calls')->onDelete('set null');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('call_control_id')->nullable();
            $table->string('call_leg_id')->nullable();
            $table->string('call_session_id')->nullable();
            $table->string('conference_id')->nullable();
            $table->string('channels')->default('dual'); // dual, single
            $table->string('source')->nullable(); // conference, call
            $table->string('status')->default('processing'); // processing, completed, deleted
            $table->integer('duration_millis')->nullable();
            $table->string('download_url_mp3', 500)->nullable();
            $table->string('download_url_wav', 500)->nullable();
            $table->timestamp('recording_started_at')->nullable();
            $table->timestamp('recording_ended_at')->nullable();
            $table->timestamps();
            
            $table->index('call_session_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recordings');
    }
};
