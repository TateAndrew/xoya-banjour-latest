<?php

namespace App\Events;

use App\Models\Call;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $call;
    public $status;
    public $eventType;

    /**
     * Create a new event instance.
     */
    public function __construct(Call $call, string $status, string $eventType)
    {
        $this->call = $call;
        $this->status = $status;
        $this->eventType = $eventType;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('call-status'),
            new Channel('call-status.' . $this->call->call_session_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'call.status.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'call_session_id' => $this->call->call_session_id,
            'call_control_id' => $this->call->call_control_id,
            'call_id' => $this->call->id,
            'status' => $this->status,
            'event_type' => $this->eventType,
            'from_number' => $this->call->from_number,
            'to_number' => $this->call->to_number,
            'direction' => $this->call->direction,
            'start_time' => $this->call->start_time,
            'answered_at' => $this->call->answered_at,
            'ended_at' => $this->call->ended_at,
            'duration' => $this->call->duration,
            'timestamp' => now()->toISOString(),
        ];
    }
}
