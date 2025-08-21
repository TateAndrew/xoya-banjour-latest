<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConferenceParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'conference_id',
        'user_id',
        'phone_number',
        'participant_name',
        'role',
        'status',
        'joined_at',
        'left_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'joined_at' => 'datetime',
        'left_at' => 'datetime',
    ];

    public function conference(): BelongsTo
    {
        return $this->belongsTo(Conference::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isMuted(): bool
    {
        return $this->status === 'muted';
    }

    public function isHost(): bool
    {
        return $this->role === 'host';
    }

    public function isModerator(): bool
    {
        return $this->role === 'moderator';
    }

    public function getDurationAttribute(): int
    {
        if (!$this->joined_at) return 0;
        
        $endTime = $this->left_at ?? now();
        return $endTime->diffInSeconds($this->joined_at);
    }

    public function getDurationFormattedAttribute(): string
    {
        $duration = $this->duration;
        if (!$duration) return '00:00';
        
        $minutes = floor($duration / 60);
        $seconds = $duration % 60;
        
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}
