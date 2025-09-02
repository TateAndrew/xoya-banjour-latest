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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 64)->nullable(); // CRM/ERP id (optional)
            $table->string('name', 191)->nullable();
            $table->string('phone_e164', 32)->unique(); // e.g. +15551234567
            $table->timestamps();
            
            // Indexes
            $table->index('external_id');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
