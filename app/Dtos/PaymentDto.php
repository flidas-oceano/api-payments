<?php

namespace App\Dtos;

use App\Enums\GatewayEnum;

class PaymentDto
{
    private $soNumber;

    private $paymentId;

    private $payDate;

    private $id;

    private $amountCharged;

    private $subId;

    private $chargeId;

    private $contactId;

    private $contractId;

    private $numberInstallment;

    private $fee;

    private $paymentOrigin;

    private $externalNumber;

    private $numberSo;

    private $numberSoOm;

    private $paymentDate;

    public function __construct($data)
    {
        $this->soNumber = $data['number_so_om'] ?? null;
        $this->paymentId = $data['payment_id'] ?? null;
        $this->payDate = $data['pay_date'] ?? null;
        $this->id = $data['id'] ?? null;
        $this->amountCharged = $data['amount_charged'] ?? null;
        $this->subId = $data['sub_id'] ?? null;
        $this->chargeId = $data['charge_id'] ?? null;
        $this->contactId = $data['contact_id'] ?? null;
        $this->contractId = $data['contract_id'] ?? null;
        $this->numberInstallment = $data['number_installment'] ?? null;
        $this->fee = $data['fee'] ?? null;
        $this->paymentOrigin = $data['payment_origin'] ?? null;
        $this->externalNumber = $data['external_number'] ?? null;
        $this->numberSo = $data['number_so'] ?? null;
        $this->numberSoOm = $data['number_so_om'] ?? null;
        $this->paymentDate = $data['payment_date'] ?? null;
    }

    /**
     * @return mixed|null
     */
    public function getSoNumber()
    {
        return $this->soNumber;
    }

    /**
     * @return mixed|null
     */
    public function getPaymentId()
    {
        return $this->paymentId;
    }

    /**
     * @return mixed|null
     */
    public function getPayDate()
    {
        return $this->payDate;
    }

    /**
     * @return mixed|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed|null
     */
    public function getAmountCharged()
    {
        return $this->amountCharged;
    }

    /**
     * @return mixed|null
     */
    public function getSubId()
    {
        return $this->subId;
    }

    /**
     * @return mixed|null
     */
    public function getChargeId()
    {
        return $this->chargeId;
    }

    /**
     * @return mixed|null
     */
    public function getContactId()
    {
        return $this->contactId;
    }

    /**
     * @return mixed|null
     */
    public function getContractId()
    {
        return $this->contractId;
    }

    /**
     * @return mixed|null
     */
    public function getNumberInstallment()
    {
        return $this->numberInstallment;
    }

    /**
     * @return mixed|null
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * @return mixed|null
     */
    public function getPaymentOrigin()
    {
        return $this->paymentOrigin;
    }

    /**
     * @return mixed|null
     */
    public function getExternalNumber()
    {
        return $this->externalNumber;
    }

    /**
     * @return mixed|null
     */
    public function getNumberSo()
    {
        return $this->numberSo;
    }

    /**
     * @return mixed|null
     */
    public function getNumberSoOm()
    {
        return $this->numberSoOm;
    }

    /**
     * @return mixed|null
     */
    public function getPaymentDate()
    {
        return $this->paymentDate;
    }
}
