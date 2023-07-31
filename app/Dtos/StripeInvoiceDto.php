<?php

namespace App\Dtos;
class StripeInvoiceDto
{
    private ?string $id;
    private ?string $subscription;

    private ?int $amount;
    private ?string $customerId;
    private ?string $numberSoOm;

    private ?string $invoiceReference;

    public function __construct($data)
    {
        $this->id = $data['id'] ?? null;
        $this->subscription = $data['subscription'] ?? null;
        $lines = $data['lines']['data'][0];
        $this->amount = $lines['amount'];
        $this->customerId = $data['customer'] ?? null;
        $this->numberSoOm = $lines['metadata']['SO_Number'] ?? null;
        $this->invoiceReference = $data['number'];
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getSubscription(): ?string
    {
        return $this->subscription;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }

    public function getNumberSoOm(): ?string
    {
        return $this->numberSoOm;
    }

    public function getInvoiceReference(): ?string
    {
        return $this->invoiceReference;
    }
}
