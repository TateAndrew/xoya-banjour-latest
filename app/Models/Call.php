<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Call extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'phone_number_id',
        'from_number',
        'to_number',
        'from_sip_uri',
        'to_sip_uri',
        'call_type',
        'status',
        'telnyx_call_id',
        'call_control_id',
        'call_leg_id',
        'call_session_id',
        'sip_trunk_id',
        'conference_id',
        'connection_id',
        'direction',
        'calling_party_type',
        'state',
        'start_time',
        'answered_at',
        'ended_at',
        'duration',
        'hangup_cause',
        'hangup_source',
        'sip_hangup_cause',
        'call_quality_stats',
        'custom_headers',
        'client_state',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'custom_headers' => 'array',
        'call_quality_stats' => 'array',
        'answered_at' => 'datetime',
        'ended_at' => 'datetime',
        'start_time' => 'datetime',
        'cost' => 'decimal:4',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function phoneNumber(): BelongsTo
    {
        return $this->belongsTo(PhoneNumber::class);
    }

    public function conference(): BelongsTo
    {
        return $this->belongsTo(Conference::class, 'conference_id', 'conference_id');
    }

    public function isActive(): bool
    {
        return in_array($this->status, ['initiating', 'ringing', 'answered', 'in_progress']);
    }

    public function isEnded(): bool
    {
        return in_array($this->status, ['ended', 'failed']);
    }

    public function isVideoCall(): bool
    {
        return $this->call_type === 'video';
    }

    public function isConferenceCall(): bool
    {
        return $this->call_type === 'conference';
    }

    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration) return '00:00';
        
        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;
        
        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function getCostFormattedAttribute(): string
    {
        if (!$this->cost) return '$0.00';
        return '$' . number_format($this->cost, 2);
    }

    public static function getUserCallHistory(int $userId, int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
