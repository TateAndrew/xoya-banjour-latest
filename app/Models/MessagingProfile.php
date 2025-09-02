<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class MessagingProfile extends Model
{
    protected $fillable = [
        'user_id',
        'telnyx_profile_id',
        'name',
        'whitelisted_destinations',
        'enabled',
        'webhook_url',
        'webhook_failover_url',
        'webhook_api_version',
        'number_pool_settings',
        'url_shortener_settings',
        'alpha_sender',
        'daily_spend_limit',
        'daily_spend_limit_enabled',
        'mms_fall_back_to_sms',
        'mms_transcoding',
        'metadata',
    ];

    protected $casts = [
        'whitelisted_destinations' => 'array',
        'enabled' => 'boolean',
        'number_pool_settings' => 'array',
        'url_shortener_settings' => 'array',
        'daily_spend_limit_enabled' => 'boolean',
        'mms_fall_back_to_sms' => 'boolean',
        'mms_transcoding' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Get the user that owns the messaging profile
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the phone numbers assigned to this messaging profile
     */
    public function phoneNumbers(): HasMany
    {
        return $this->hasMany(PhoneNumber::class);
    }

    /**
     * Get only the active phone numbers assigned to this messaging profile
     */
    public function activePhoneNumbers(): HasMany
    {
        return $this->hasMany(PhoneNumber::class)->where('status', 'purchased');
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
     * Get the currently active messaging profile for a user
     */
    public static function getActiveForUser(int $userId): ?self
    {
        return static::where('user_id', $userId)
            ->where('enabled', true)
            ->first();
    }

    /**
     * Activate this messaging profile and deactivate all others for the same user
     */
    public function activate(): bool
    {
        return DB::transaction(function () {
            // Deactivate all other profiles for this user
            static::where('user_id', $this->user_id)
                ->where('id', '!=', $this->id)
                ->update(['enabled' => false]);

            // Activate this profile
            $this->update(['enabled' => true]);

            return true;
        });
    }

    /**
     * Deactivate this messaging profile
     */
    public function deactivate(): bool
    {
        return $this->update(['enabled' => false]);
    }

    /**
     * Check if this is the active profile for the user
     */
    public function isActive(): bool
    {
        return $this->enabled;
    }
}
