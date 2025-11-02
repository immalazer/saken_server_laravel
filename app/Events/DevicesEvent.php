<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DevicesEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public String $reason;
    public String $origin;
    public String $deviceId;
    public String $filename;
    public ?String $extra;
    
    public function __construct(
        String $reason,
        String $origin,
        String $deviceId,
        String $filename,
        String $extra = null
    )
    {
        $this->reason = $reason;
        $this->origin = $origin;
        $this->deviceId = $deviceId;
        $this->filename = $filename;
        $this->extra = $extra;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('device-controls'),
        ];
    }

    public function broadcastWith(): array
     {
        return [
            'message' => $this->reason,
            'origin' => $this->origin,
            'deviceId' => $this->deviceId,
            'filename' => $this->filename,
            'extra' => $this->extra,
        ];
    }
}
