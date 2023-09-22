<?php

namespace App\Dtos\Contifico;

class ContificoInvoiceChargeDto
{
    protected ?string $id;
    protected ?string $invoiceId;
    protected ?string $chargeMethod;
    protected ?string $chargeType;
    protected ?string $date;
    protected ?float $valueCharged;
    public function __construct($data)
    {
        $this->id = $data['id'] ?? null;
        $this->invoiceId = $data['invoice_id'] ?? null;
        $this->chargeMethod = $data['charge_method'] ?? null;
        $this->date = $data['date'] ?? null;
        $this->valueCharged = $data['value_charged'] ?? 0;
        $this->chargeType = $data['charge_type'] ?? null;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getInvoiceId(): ?string
    {
        return $this->invoiceId;
    }

    public function getChargeMethod(): ?string
    {
        return $this->chargeMethod;
    }

    public function getChargeType(): ?string
    {
        return $this->chargeType;
    }

    public function getDate(): ?string
    {
        return $this->date;
    }

    public function getValueCharged(): ?float
    {
        return $this->valueCharged;
    }
}
