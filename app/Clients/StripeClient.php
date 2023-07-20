<?php

namespace App\Clients;

use App\Interfaces\IClient;

class StripeClient implements IClient
{
    private \Stripe\StripeClient $stripe;

    public function __construct()
    {
        $skKey = env('STRIPE_MX_SK_MSK_PROD');
        $this->stripe = new \Stripe\StripeClient($skKey);
    }

    public function getClient(): \Stripe\StripeClient
    {
        return $this->stripe;
    }
}
