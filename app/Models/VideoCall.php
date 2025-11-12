<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class VideoCall extends Model
{
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_ACTIVE = 'active';
    const STATUS_ENDED = 'ended';
    const STATUS_CANCELLED = 'cancelled';

    const TYPE_ONE_TO_ONE = 'one_to_one';
    const TYPE_GROUP = 'group';
    const TYPE_CONFERENCE = 'conference';

    protected $fillable = [
        'room_name',
        'host_user_id',     
        'participant_user_id',
        'contact_id',
        'status',
        'type',
        'started_at',
        'ended_at',
        'duration',
        'participants',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'participants' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the host user for the video call.
     */
    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_user_id');
    }

    /**
     * Get the participant user for the video call.
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_user_id');
    }

    /**
     * Get the contact for the video call.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Generate a unique room name.
     */
    public static function generateRoomName(): string
    {
        do {
            $roomName = 'room-' . Str::random(12);
        } while (self::where('room_name', $roomName)->exists());

        return $roomName;
    }

    /**
     * Start the video call.
     */
    public function start(): void
    {
        $this->update([
            'status' => self::STATUS_ACTIVE,
            'started_at' => now(),
        ]);
    }

    /**
     * End the video call.
     */
    public function end(): void
    {
        $duration = null;
        if ($this->started_at) {
            $duration = now()->diffInSeconds($this->started_at);
        }

        $this->update([
            'status' => self::STATUS_ENDED,
            'ended_at' => now(),
            'duration' => $duration,
        ]);
    }

    /**
     * Add a participant to the call.
     */
    public function addParticipant(array $participantData): void
    {
        $participants = $this->participants ?? [];
        $participants[] = array_merge($participantData, [
            'joined_at' => now()->toISOString(),
        ]);
        
        $this->update(['participants' => $participants]);
    }

    /**
     * Remove a participant from the call.
     */
    public function removeParticipant(string $participantId): void
    {
        $participants = $this->participants ?? [];
        $participants = array_filter($participants, function ($p) use ($participantId) {
            return ($p['id'] ?? null) !== $participantId;
        });
        
        $this->update(['participants' => array_values($participants)]);
    }

    /**
     * Check if the call is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if user can join the call.
     */
    public function canUserJoin(int $userId): bool
    {
        // Host can always join
        if ($this->host_user_id === $userId) {
            return true;
        }

        // For one-to-one calls, check if user is the participant
        if ($this->type === self::TYPE_ONE_TO_ONE) {
            return $this->participant_user_id === $userId;
        }

        // For group/conference calls, allow any authenticated user
        // You can add more sophisticated logic here
        return true;
    }
}
