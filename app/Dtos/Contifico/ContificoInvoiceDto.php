<?php

namespace App\Dtos\Contifico;

class ContificoInvoiceDto
{
    protected ?string $id;
    protected ?ContificoUserDto $customer;
    protected ?array $products;
    protected ?string $invoiceNumber;
    protected ?string $status; //P:pendiente, C:cobrado, G:pagado, A:anulado, E:generado, F:facturado
    protected ?string $orderNumber;
    protected ?string $invoiceDate;
    protected ?string $methodOfPayment;

    protected ?float $subTotal;
    protected ?float $subTotalIva;
    protected ?float $shipping;
    protected ?float $iva;
    protected ?float $adjust;
    protected ?float $total;
    protected ?string $authorization;
    protected ?array $responseSri;

    public function __construct($data)
    {
        $this->id = $data['id'] ?? null;
        $this->invoiceNumber = $data['invoice_number'] ?? null;
        $this->orderNumber = $data['order_number'] ?? null;
        $this->invoiceDate = $data['invoice_date'] ?? null;
        $this->methodOfPayment = $data['method_payment'] ?? null;
        $this->subTotal = $data['sub_total_0'] ?? 0;
        $this->subTotalIva = $data['sub_total_12'] ?? 0;
        $this->shipping = $data['shipping'] ?? 0;
        $this->iva = $data['iva'] ?? 0;
        $this->adjust = $data['adjust'] ?? 0;
        $this->total = $data['total'] ?? 0;
        $products = $data['products'] ?? [];
        $this->products = $products ? $this->constructProducts($products) : [];
        $customer = $data['customer'] ?? [];
        $this->customer = $customer? new ContificoUserDto($customer) : null;
        $this->status = $data['status'] ?? null;
        $this->authorization = $data['authorization'] ?? null;
    }
    public function constructProducts($products): array
    {
        $response = [];
        foreach ($products as $product) {
            $response[] = new ContificoInvoiceDetailsDto($product);
        }

        return $response;
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
    /**
     * @return ContificoUserDto
     */
    public function getCustomer(): ?ContificoUserDto
    {
        return $this->customer;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function getSubTotalIva(): ?float
    {
        return $this->subTotalIva;
    }

    public function getAuthorization(): ?string
    {
        return $this->authorization;
    }

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getResponseSri(): ?array
    {
        return $this->responseSri;
    }

    public function setResponseSri(?array $responseSri): void
    {
        $this->responseSri = $responseSri;
    }
}
