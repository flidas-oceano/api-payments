<?php

namespace App\Dtos\Contifico;

class ContificoInvoiceDetailsDto
{
    protected ?string $productId;
    protected ?string $quantity;
    protected ?float $price;
    protected ?int $percentageIva;
    protected ?int $percentageDiscount;
    protected ?float $recordableBase;
    protected ?float $nonRecordableBase;
    protected ?float $zeroBase;


    public function __construct($data)
    {
        $this->productId = $data['product_id'] ?? null;
        $this->quantity = $data['quantity'] ?? null;
        $this->price = $data['price'] ?? null;
        $this->percentageIva = $data['percentage_iva'] ?? null;
        $this->percentageDiscount = $data['percentage_discount'] ?? null;
        $this->recordableBase = $data['recordable_base'] ?? null;
        $this->nonRecordableBase = $data['non_recordable_base'] ?? null;
        $this->zeroBase = $data['zero_base'] ?? null;
    }

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function getQuantity(): ?string
    {
        return $this->quantity;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function getPercentageIva(): ?int
    {
        return $this->percentageIva;
    }

    public function getPercentageDiscount(): ?float
    {
        return $this->percentageDiscount;
    }

    public function getRecordableBase(): ?string
    {
        return $this->recordableBase;
    }

    public function getNonRecordableBase(): ?float
    {
        return $this->nonRecordableBase;
    }

    public function getZeroBase(): ?float
    {
        return $this->zeroBase;
    }
}
