<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StripePaymentEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $type;
    public $payload;
    public $status;
    public $userId;

    /**
     * Create a new event instance.
     *
     * @param string $type The specific Stripe event type (e.g., 'checkout.session.completed')
     * @param array $payload The data associated with the event
     * @param string $status The status of the event (e.g., 'success', 'failed')
     * @param int|null $userId The ID of the user associated with the event, if available
     */
    public function __construct(string $type, array $payload, string $status, ?int $userId = null)
    {
        $this->type = $type;
        $this->payload = $payload;
        $this->status = $status;
        $this->userId = $userId;
    }
}
