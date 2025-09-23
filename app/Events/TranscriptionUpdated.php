<?php

namespace App\Events;

use App\Models\CallTranscript;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TranscriptionUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transcript;
    public $callControlId;
    public $transcriptionData;

        /**
         * Create a new event instance.
         */
        public function __construct(CallTranscript $transcript, array $transcriptionData = [])
        {
            $this->transcript = $transcript;
            $this->callControlId = $transcript->call_control_id;
            $this->transcriptionData = $transcriptionData;
        }

        /**
         * Get the channels the event should broadcast on.
         *
         * @return array<int, \Illuminate\Broadcasting\Channel>
         */
        public function broadcastOn(): array
        {
            return [
                // A global channel for all call transcriptions
                new Channel('call-transcription'),

                // If you want per-call channel (uncomment this line):
                // new Channel('call-transcription.' . $this->callControlId),
            ];
        }

        /**
         * The event's broadcast name.
         */
        public function broadcastAs(): string
        {
            return 'transcription.updated';
        }

        /**
         * Get the data to broadcast.
         */
        public function broadcastWith(): array
        {
            return [
                'call_control_id'   => $this->callControlId,
                'transcript_id'     => $this->transcript->id,
                'call_id'           => $this->transcript->call_id,
                'transcript_text'   => $this->transcript->transcript_text,
                'status'            => $this->transcript->status,
                'language'          => $this->transcript->language,
                'is_final'          => $this->transcriptionData['is_final'] ?? false,
                'confidence'        => $this->transcriptionData['confidence'] ?? null,
                'latest_transcript' => $this->transcriptionData['transcript'] ?? null,
                'timestamp'         => now()->toISOString(),
                'transcript_data'   => $this->transcript->transcript_data,
            ];
        }
}
