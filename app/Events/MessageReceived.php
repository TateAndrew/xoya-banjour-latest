<?php

namespace App\Events;

use App\Models\Message;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MessageReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;
    public $conversation;
    public $userId;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message, Conversation $conversation, $userId)
    {
        $this->message = $message;
        $this->conversation = $conversation->load('contact');
        $this->userId = $userId;
        Log::info('MessageReceived event constructor', [
            'message' => $this->message,
            'conversation' => $this->conversation,
            'channel name' => 'user.'.$this->userId
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('user.' . $this->userId),
        ];
    }
    public function broadcastAs(): string
    {
        return 'message.received';
    }
    

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'conversation' => $this->conversation,
        ];
    }
}
