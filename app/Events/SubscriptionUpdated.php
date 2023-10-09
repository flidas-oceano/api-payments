<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $subscription;

    public function __construct($subscription)
    {
        $this->subscription = $subscription;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }

    public function handle()
    {
        if ($this->subscription->failed_payment_attempts > 2) {
            // Ejecuta tu función para suspender la suscripción aquí
            $this->suspendSubscription();
        }
    }

    private function suspendSubscription()
    {
        // Agrega tu lógica para suspender la suscripción aquí
        // Por ejemplo, cambia el estado de la suscripción a "suspendida"
        $this->subscription->update(['status' => 'SUSPENDED']);
    }
}