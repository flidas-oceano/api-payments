<?php

namespace App\Dtos\Contifico;

class ContificoInvoiceDto
{
    protected ?string $id;
    protected ?array $customer;
    protected ?array $products;
    protected ?string $invoiceNumber;
    protected ?string $status; //P:pendiente, C:cobrado, G:pagado, A:anulado, E:generado, F:facturado
    protected ?string $orderNumber;
    protected ?string $invoiceDate;
    protected ?string $methodOfPayment;

    protected ?float $subTotal;
    protected ?float $shipping;
    protected ?float $iva;
    protected ?float $adjust;
    protected ?float $total;

    public function __construct($data)
    {
        $this->id = $data['id'] ?? null;
        $this->invoiceNumber = $data['invoiceNumber'] ?? null;
        $this->orderNumber = $data['orderNumber'] ?? null;
        $this->invoiceDate = $data['invoiceDate'] ?? null;
        $this->methodOfPayment = $data['methodOfPayment'] ?? null;
        $this->subTotal = $data['subTotal'] ?? 0;
        $this->shipping = $data['shipping'] ?? 0;
        $this->iva = $data['iva'] ?? 0;
        $this->adjust = $data['adjust'] ?? 0;
        $this->total = $data['total'] ?? 0;
        $products = $data['products'] ?? [];
        $this->products = $products ? function($products) {
            $response = [];
            foreach ($products as $product) {
                $response[] = new ContificoInvoiceDetailsDto($product);
            }
            return $response;
        } : [];
        $customer = $data['customer'] ?? [];
        $this->customer = $customer? new ContificoUserDto($customer) : [];
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getInvoiceNumber(): ?string
    {
        return $this->invoiceNumber;
    }

    public function getOrderNumber(): ?string
    {
        return $this->orderNumber;
    }

    public function getInvoiceDate(): ?string
    {
        return $this->invoiceDate;
    }

    public function getMethodOfPayment(): ?string
    {
        return $this->methodOfPayment;
    }

    public function getProducts(): ?array
    {
        return $this->products;
    }

    public function getSubTotal(): ?float
    {
        return $this->subTotal;
    }

    public function getShipping(): ?float
    {
        return $this->shipping;
    }

    public function getIva(): ?float
    {
        return $this->iva;
    }

    public function getAdjust(): ?float
    {
        return $this->adjust;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }
}
