<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EkpayPaymentEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $trnxId;
    public $payload;
    public $status;
    public $userId;

    /**
     * Create a new event instance.
     *
     * @param string $trnxId The transaction ID
     * @param array $payload The IPN payload
     * @param string $status The status (succeeded, failed, unknown)
     * @param int|null $userId The user ID
     */
    public function __construct(string $trnxId, array $payload, string $status, ?int $userId = null)
    {
        $this->trnxId = $trnxId;
        $this->payload = $payload;
        $this->status = $status;
        $this->userId = $userId;
    }
}
