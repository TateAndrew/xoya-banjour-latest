<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_id',
        'event_type',
        'event_id',
        'occurred_at',
        'call_control_id',
        'call_leg_id',
        'call_session_id',
        'connection_id',
        'direction',
        'calling_party_type',
        'state',
        'from_number',
        'to_number',
        'from_sip_uri',
        'to_sip_uri',
        'start_time',
        'end_time',
        'hangup_cause',
        'hangup_source',
        'sip_hangup_cause',
        'call_quality_stats',
        'custom_headers',
        'client_state',
        'raw_payload',
        'meta',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'call_quality_stats' => 'array',
        'custom_headers' => 'array',
        'raw_payload' => 'array',
        'meta' => 'array',
    ];

    /**
     * Get the call that owns the log entry
     */
    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * Scope to filter by event type
     */
    public function scopeByEventType($query, $eventType)
    {
        return $query->where('event_type', $eventType);
    }

    /**
     * Scope to filter by call control ID
     */
    public function scopeByCallControlId($query, $callControlId)
    {
        return $query->where('call_control_id', $callControlId);
    }

    /**
     * Get formatted event type for display
     */
    public function getFormattedEventTypeAttribute(): string
    {
        return match($this->event_type) {
            'call.initiated' => 'Call Initiated',
            'call.answered' => 'Call Answered',
            'call.bridged' => 'Call Bridged',
            'call.hangup' => 'Call Hangup',
            'call.failed' => 'Call Failed',
            default => ucfirst(str_replace('call.', '', $this->event_type))
        };
    }

    /**
     * Get duration if this log has start and end times
     */
    public function getDurationAttribute(): ?int
    {
        if ($this->start_time && $this->end_time) {
            return $this->start_time->diffInSeconds($this->end_time);
        }
        return null;
    }

    /**
     * Check if this is a hangup event
     */
    public function isHangupEvent(): bool
    {
        return $this->event_type === 'call.hangup';
    }

    /**
     * Check if this is an answer event
     */
    public function isAnswerEvent(): bool
    {
        return $this->event_type === 'call.answered';
    }

    /**
     * Get call quality metrics if available
     */
    public function getCallQualityMetrics(): ?array
    {
        return $this->call_quality_stats;
    }

    /**
     * Get MOS score if available
     */
    public function getMosScore(): ?float
    {
        return $this->call_quality_stats['inbound']['mos'] ?? null;
    }
}