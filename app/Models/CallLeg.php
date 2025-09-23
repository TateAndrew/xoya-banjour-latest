<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallLeg extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_session_id',
        'call_leg_id',
        'call_control_id',
        'event_type',
        'event_id',
        'occurred_at',
        'direction',
        'calling_party_type',
        'from_number',
        'to_number',
        'from_sip_uri',
        'to_sip_uri',
        'state',
        'start_time',
        'end_time',
        'hangup_cause',
        'hangup_source',
        'sip_hangup_cause',
        'call_quality_stats',
        'custom_headers',
        'client_state',
        'metadata',
    ];

    protected $casts = [
        'custom_headers' => 'array',
        'call_quality_stats' => 'array',
        'metadata' => 'array',
        'occurred_at' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Get the call session this leg belongs to
     */
    public function callSession(): BelongsTo
    {
        return $this->belongsTo(CallSession::class);
    }

    /**
     * Find call leg by Telnyx event ID
     */
    public static function findByEventId($eventId)
    {
        return static::where('event_id', $eventId)->first();
    }

    /**
     * Check if this is an initiation event
     */
    public function isInitiation(): bool
    {
        return $this->event_type === 'call.initiated';
    }

    /**
     * Check if this is an answer event
     */
    public function isAnswer(): bool
    {
        return $this->event_type === 'call.answered';
    }

    /**
     * Check if this is a bridge event
     */
    public function isBridge(): bool
    {
        return $this->event_type === 'call.bridged';
    }

    /**
     * Check if this is a hangup event
     */
    public function isHangup(): bool
    {
        return $this->event_type === 'call.hangup';
    }

    /**
     * Check if this is a failure event
     */
    public function isFailure(): bool
    {
        return $this->event_type === 'call.failed';
    }

    /**
     * Get duration in seconds if both start and end times are available
     */
    public function getDurationAttribute(): ?int
    {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }
        
        return $this->start_time->diffInSeconds($this->end_time);
    }

    /**
     * Get formatted duration
     */
    public function getFormattedDurationAttribute(): string
    {
        $duration = $this->duration;
        if (!$duration) return '00:00';
        
        $minutes = floor($duration / 60);
        $seconds = $duration % 60;
        
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}
