<?php

namespace App\Events;

use App\Models\Songs;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateNotifierEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public Songs $songs;

    public function __construct(Songs $songs)
    {
        $this->songs = $songs;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('songs-updates'),
        ];
    }

    public function broadcastWith(): array
     {
        return [
            'message' => 'update',
            'filename' => $this->songs->filename,
        ];
    }
}
