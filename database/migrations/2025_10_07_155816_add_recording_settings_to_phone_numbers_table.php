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
        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->boolean('inbound_call_recording_enabled')->default(false);
            $table->string('inbound_call_recording_format')->default('wav');
            $table->string('inbound_call_recording_channels')->default('single');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('phone_numbers', function (Blueprint $table) {
            $table->dropColumn(['inbound_call_recording_enabled', 'inbound_call_recording_format', 'inbound_call_recording_channels']);
        });
    }
};
