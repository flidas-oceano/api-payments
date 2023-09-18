<?php

namespace App\Dtos\Contifico;

class ContificoInvoiceDetailsDto
{
    protected ?string $productId;
    protected ?string $quantity;
    protected ?string $price;
    protected ?int $percentageIva;
    protected ?float $percentageDiscount;
    protected ?string $recordableBase;

    public function __construct($data)
    {
        $this->productId = $data['product_id'] ?? null;
        $this->quantity = $data['quantity'] ?? null;
        $this->price = $data['price'] ?? null;
        $this->percentageIva = $data['percentage_iva'] ?? null;
        $this->percentageDiscount = $data['percentage_discount'] ?? null;
        $this->recordableBase = $data['recordable_base'] ?? null;
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
}
