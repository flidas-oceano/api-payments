<?php

namespace App\Services\Stripe;


use App\Clients\StripeClient;
use App\Dtos\MpSearchDto;
use App\Dtos\StripeInvoiceDto;
use App\Dtos\StripeInvoiceSearchDto;
use App\Dtos\StripePaymentSearchDto;
use App\Interfaces\IReadPayment;
use App\Interfaces\ISearchDto;
use App\Services\MercadoPago\RestApi;
use Stripe\Exception\ApiErrorException;

class ReadPayment implements IReadPayment
{
    private \Stripe\StripeClient $api;

    public function __construct(StripeClient $client)
    {
        $this->api = $client->getClient();
    }
    public function findBySucceeded(): StripePaymentSearchDto
    {
        $data = $this->api->paymentIntents->search(['query' => 'status:"succeeded"'])->toArray();

        return new StripePaymentSearchDto($data);
    }

    /**
     * @throws ApiErrorException
     */
    public function findInvoiceByInvoiceId($invoice): StripeInvoiceDto
    {
        $response = $this->api->invoices->retrieve($invoice)->toArray();

        return new StripeInvoiceDto($response);
    }

    public function findById($id, $country)
    {
        // TODO: Implement findById() method.
    }
}
