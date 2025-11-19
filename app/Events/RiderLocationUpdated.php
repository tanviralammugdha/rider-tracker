<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RiderLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $rider;

    /**
     * Create a new event instance.
     */
    public function __construct($rider)
    {
        // আমরা রাইডারের আইডি, নাম এবং লোকেশন পাঠাবো
        $this->rider = [
            'id' => $rider->id,
            'name' => $rider->name,
            'lat' => $rider->latitude,
            'lng' => $rider->longitude,
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        // 'live-tracking' নামের একটি পাবলিক চ্যানেলে ডাটা পাঠানো হবে
        return [
            new Channel('live-tracking'),
        ];
    }
}