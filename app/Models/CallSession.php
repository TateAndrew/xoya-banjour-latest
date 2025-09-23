<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CallSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_id',
        'user_id',
        'phone_number_id',
        'call_session_id',
        'from_number',
        'to_number',
        'from_sip_uri',
        'to_sip_uri',
        'call_type',
        'direction',
        'calling_party_type',
        'connection_id',
        'sip_trunk_id',
        'conference_id',
        'status',
        'start_time',
        'end_time',
        'duration',
        'custom_headers',
        'metadata',
    ];

    protected $casts = [
        'custom_headers' => 'array',
        'metadata' => 'array',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'duration' => 'integer',
    ];

    /**
     * Get the call this session belongs to
     */
    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * Get the user that owns this session
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the phone number used for this session
     */
    public function phoneNumber(): BelongsTo
    {
        return $this->belongsTo(PhoneNumber::class);
    }

    /**
     * Get the SIP trunk used for this session
     */
    public function sipTrunk(): BelongsTo
    {
        return $this->belongsTo(SipTrunk::class);
    }

    /**
     * Get the conference this session belongs to
     */
    public function conference(): BelongsTo
    {
        return $this->belongsTo(Conference::class, 'conference_id', 'conference_id');
    }

    /**
     * Get all call legs for this session
     */
    public function callLegs(): HasMany
    {
        return $this->hasMany(CallLeg::class);
    }

    /**
     * Find call session by Telnyx session ID
     */
    public static function findBySessionId($sessionId)
    {
        return static::where('call_session_id', $sessionId)->first();
    }

    /**
     * Check if session is active
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['initiating', 'answered', 'in_progress']);
    }

    /**
     * Check if session is ended
     */
    public function isEnded(): bool
    {
        return in_array($this->status, ['ended', 'failed']);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration) return '00:00';
        
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}
