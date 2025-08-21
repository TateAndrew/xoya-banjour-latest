<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conference extends Model
{
    use HasFactory;

    protected $fillable = [
        'conference_id',
        'host_id',
        'title',
        'status',
        'max_participants',
        'settings',
        'scheduled_at',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'settings' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConferenceParticipant::class);
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class, 'conference_id', 'conference_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isEnded(): bool
    {
        return $this->status === 'ended';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function getActiveParticipantsCountAttribute(): int
    {
        return $this->participants()->where('status', 'active')->count();
    }

    public function getTotalParticipantsCountAttribute(): int
    {
        return $this->participants()->count();
    }

    public function getDurationAttribute(): int
    {
        if (!$this->started_at || !$this->ended_at) return 0;
        return $this->ended_at->diffInSeconds($this->started_at);
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
