<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Recording extends Model
{
    use HasFactory;

    protected $fillable = [
        'telnyx_recording_id',
        'call_id',
        'user_id',
        'call_control_id',
        'call_leg_id',
        'call_session_id',
        'conference_id',
        'channels',
        'source',
        'status',
        'duration_millis',
        'download_url_mp3',
        'download_url_wav',
        'recording_started_at',
        'recording_ended_at',
    ];

    protected $casts = [
        'recording_started_at' => 'datetime',
        'recording_ended_at' => 'datetime',
        'duration_millis' => 'integer',
    ];

    /**
     * Get the call that owns this recording.
     */
    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * Get the user associated with this recording.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get formatted duration.
     */
    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration_millis) {
            return '00:00';
        }
        
        $seconds = floor($this->duration_millis / 1000);
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        return sprintf('%02d:%02d', $minutes, $remainingSeconds);
    }

    /**
     * Get duration in seconds.
     */
    public function getDurationSecondsAttribute(): int
    {
        return $this->duration_millis ? floor($this->duration_millis / 1000) : 0;
    }

    /**
     * Check if recording is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if recording has download URLs.
     */
    public function hasDownloadUrls(): bool
    {
        return !empty($this->download_url_mp3) || !empty($this->download_url_wav);
    }
}
