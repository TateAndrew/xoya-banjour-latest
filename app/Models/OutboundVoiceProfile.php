<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class OutboundVoiceProfile extends Model
{
    protected $fillable = [
        'user_id',
        'telnyx_profile_id',
        'name',
        'traffic_type',
        'service_plan',
        'concurrent_call_limit',
        'enabled',
        'tags',
        'max_destination_rate',
        'daily_spend_limit',
        'daily_spend_limit_enabled',
        'call_recording_type',
        'call_recording_caller_phone_numbers',
        'call_recording_callee_phone_numbers',
        'call_recording_channels',
        'call_recording_format',
        'billing_group_id',
        'metadata',
    ];

    protected $casts = [
        'traffic_type' => 'boolean',
        'concurrent_call_limit' => 'integer',
        'enabled' => 'boolean',
        'max_destination_rate' => 'integer',
        'daily_spend_limit' => 'integer',
        'call_recording_channels' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the outbound voice profile
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include profiles for a specific user
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include enabled profiles
     */
    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('enabled', true);
    }

    /**
     * Get the traffic type as a readable string
     */
    public function getTrafficTypeNameAttribute(): string
    {
        return $this->traffic_type ? 'Short Duration' : 'Conversational';
    }

    /**
     * Check if this profile is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Check if call recording is enabled
     */
    public function hasCallRecording(): bool
    {
        return !empty($this->call_recording_type) && $this->call_recording_type !== 'none';
    }
}

