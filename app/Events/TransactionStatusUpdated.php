<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class TransactionStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transaction;
    public $message;

    public $broadcastQueue = 'transaction-update';

    /**
     * TransactionStatusUpdated constructor.
     * @param $transaction
     * @param $message
     */
    public function __construct($transaction, $message)
    {
        $this->transaction = $transaction;
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        //$account = $this->transaction->account_id
        return new PrivateChannel(getenv('APP_ENV') . '.transaction.' . $this->transaction->account_id);
    }
}
