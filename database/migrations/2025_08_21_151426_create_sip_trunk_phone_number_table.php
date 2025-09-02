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
        Schema::create('sip_trunk_phone_number', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sip_trunk_id')->constrained()->onDelete('cascade');
            $table->foreignId('phone_number_id')->constrained()->onDelete('cascade');
            $table->enum('assignment_type', ['primary', 'secondary', 'backup'])->default('primary');
            $table->boolean('is_active')->default(true);
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('last_used_at')->nullable();
            $table->json('settings')->nullable(); // Store number-specific settings
            $table->timestamps();
            
            // Ensure unique combinations
            $table->unique(['sip_trunk_id', 'phone_number_id']);
            
            // Indexes for performance
            $table->index(['sip_trunk_id', 'is_active']);
            $table->index(['phone_number_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sip_trunk_phone_number');
    }
};
