<?php

namespace App\Dtos;

class MpResultDto
{
    protected string $type;
    protected string $subscriptionId;
    protected string $invoiceId;
    protected string $billingDate;
    protected string $planId;
    protected float $amountCharged;
    protected string $payerId;
    protected string $payerEmail;
    protected string $reference;
    protected string $status;
    protected string $statusDetails;

    public function __construct($data)
    {
        $this->type = $data['operation_type'];
        $this->subscriptionId = $data['point_of_interaction']['transaction_data']['subscription_id'] ?? null;
        $this->invoiceId = $data['point_of_interaction']['transaction_data']['invoice_id'] ?? null;
        $this->billingDate = $data['point_of_interaction']['transaction_data']['billing_date'] ?? null;
        $this->planId = $data['point_of_interaction']['transaction_data']['plan_id'] ?? null;
        $this->amountCharged = $data['transaction_details']['total_paid_amount'] ?? null;
        $this->payerId = $data['payer']['id'] ?? null;
        $this->payerEmail = $data['payer']['email'] ?? null;
        $this->reference = $data['external_reference'] ?? null;
        $this->status = $data['status'] ?? null;
        $this->statusDetails = $data['status_detail'] ?? null;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getSubscriptionId(): string
    {
        return $this->subscriptionId;
    }

    /**
     * @return string
     */
    public function getInvoiceId(): string
    {
        return $this->invoiceId;
    }

    /**
     * @return string
     */
    public function getBillingDate(): string
    {
        return $this->billingDate;
    }

    /**
     * @return string
     */
    public function getPlanId(): string
    {
        return $this->planId;
    }

    /**
     * @return float
     */
    public function getAmountCharged(): float
    {
        return $this->amountCharged;
    }

    /**
     * @return string
     */
    public function getPayerId(): string
    {
        return $this->payerId;
    }

    /**
     * @return string
     */
    public function getPayerEmail(): string
    {
        return $this->payerEmail;
    }

    /**
     * @return string
     */
    public function getReference(): string
    {
        return $this->reference;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getStatusDetails(): string
    {
        return $this->statusDetails;
    }
}
