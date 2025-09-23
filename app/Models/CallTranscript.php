<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallTranscript extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_id',
        'call_control_id',
        'transcription_id',
        'status',
        'language',
        'transcript_text',
        'transcript_data',
        'started_at',
        'completed_at',
        'duration',
        'metadata',
    ];

    protected $casts = [
        'transcript_data' => 'array',
        'metadata' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration' => 'integer',
    ];

    /**
     * Get the call that owns the transcript
     */
    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by call control ID
     */
    public function scopeByCallControlId($query, $callControlId)
    {
        return $query->where('call_control_id', $callControlId);
    }

    /**
     * Check if transcription is active
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['pending', 'started', 'processing']);
    }

    /**
     * Check if transcription is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if transcription failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
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

    /**
     * Get transcript segments with timestamps
     */
    public function getTranscriptSegments(): array
    {
        return $this->transcript_data['segments'] ?? [];
    }

    /**
     * Get speaker information
     */
    public function getSpeakers(): array
    {
        return $this->transcript_data['speakers'] ?? [];
    }

    /**
     * Get confidence score
     */
    public function getConfidenceScore(): ?float
    {
        return $this->transcript_data['confidence'] ?? null;
    }

    /**
     * Get word count
     */
    public function getWordCount(): int
    {
        return str_word_count($this->transcript_text ?? '');
    }

    /**
     * Get transcript summary
     */
    public function getSummary(): array
    {
        return [
            'word_count' => $this->getWordCount(),
            'duration' => $this->formatted_duration,
            'confidence' => $this->getConfidenceScore(),
            'speakers' => count($this->getSpeakers()),
            'segments' => count($this->getTranscriptSegments()),
            'status' => $this->status,
            'language' => $this->language
        ];
    }
}