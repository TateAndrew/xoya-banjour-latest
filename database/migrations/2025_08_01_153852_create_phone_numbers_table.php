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
        Schema::create('phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('phone_number')->unique();
            $table->string('country_code', 2);
            $table->string('area_code')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('carrier')->nullable();
            $table->string('number_type')->default('local'); // local, toll-free, vanity
            $table->decimal('monthly_rate', 8, 2)->default(0);
            $table->decimal('setup_fee', 8, 2)->default(0);
            $table->string('telynx_id')->nullable(); // Telynx's internal ID
            $table->string('status')->default('available'); // available, purchased, pending, failed
            $table->json('capabilities')->nullable(); // SMS, Voice, MMS, etc.
            $table->timestamp('purchased_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable(); // Additional Telynx data
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phone_numbers');
    }
};
