<?php

namespace App\Dtos;
class StripePaymentDto
{
    private ?string $id;

    private ?string $invoiceNumber;

    private ?string $amount;
    /**
     * @var false|string
     */
    private ?string $payDate;

    public function __construct($data)
    {
        $this->id = $data['id'] ?? null;
        if (isset($data['charges']['data'][0]) && $data['charges']['data'][0]['paid']) {
            $charge = $data['charges']['data'][0];
            $this->id = $charge['id'];
            $this->invoiceNumber = $charge['invoice'];
            $this->amount = $charge['amount'] / 100;
            $this->payDate = date("Y-m-d", $charge['created']);
        }
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    /**
     * @return string|null
     */
    public function getAmount(): ?string
    {
        return $this->amount;
    }

    /**
     * @return string|null
     */
    public function getPayDate(): ?string
    {
        return $this->payDate;
    }
}
