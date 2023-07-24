<?php

namespace App\Services\Stripe;


use App\Clients\StripeClient;
use App\Dtos\StripeInvoiceDto;
use App\Dtos\StripePaymentSearchDto;
use App\Interfaces\IReadPayment;
use Stripe\Exception\ApiErrorException;

class ReadPayment implements IReadPayment
{
    private \Stripe\StripeClient $api;
    private array $paymentIntents;

    public function __construct(StripeClient $client)
    {
        $this->api = $client->getClient();
    }
    public function findBySucceeded($page = null): StripePaymentSearchDto
    {
        \Log::debug("findBySucceeded-->", [$page]);
        $query = ['query' => 'status:"succeeded"', 'limit' => 1];
        if ($page) {
            $query = array_merge($query, ['page' => $page]);
        }
        $tempPaymentIntents = $this->api->paymentIntents->search($query)->toArray();
        $this->paymentIntents[] = $tempPaymentIntents['data'][0];
        if ($tempPaymentIntents['has_more']) {
           $this->findBySucceeded($tempPaymentIntents['next_page']);
        }

        return new StripePaymentSearchDto($this->paymentIntents);
    }

    /**
     * @throws ApiErrorException
     */
    public function findInvoiceByInvoiceId($invoice): StripeInvoiceDto
    {
        $response = $this->api->invoices->retrieve($invoice)->toArray();

        return new StripeInvoiceDto($response);
    }

    public function findById($id, $country = "")
    {
        // TODO: Implement findById() method.
    }
}
