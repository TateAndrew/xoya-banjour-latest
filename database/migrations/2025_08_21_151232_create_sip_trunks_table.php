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
        Schema::create('sip_trunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('telnyx_connection_id')->nullable()->unique();
            $table->string('sip_uri')->nullable();
            $table->string('webhook_url')->nullable();
            $table->enum('status', ['active', 'inactive', 'pending', 'failed'])->default('pending');
            $table->enum('connection_type', ['sip', 'credential', 'webhook'])->default('credential');
            $table->json('credentials')->nullable(); // Store SIP credentials securely
            $table->json('settings')->nullable(); // Store additional settings
            $table->json('metadata')->nullable(); // Store Telnyx metadata
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('last_health_check')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['status', 'connection_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sip_trunks');
    }
};
