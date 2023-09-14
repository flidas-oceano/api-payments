<?php

namespace App\Dtos\Contifico;

class ContificoUserDto
{
    protected ?string $id;
    protected ?string $type;
    protected ?string $tradeName;
    protected ?string $phones;
    protected ?string $identificationCard;
    protected ?string $ruc;
    protected ?string $businessName;
    protected ?string $address;
    protected ?string $email;
    protected ?string $discountPercentage;
    protected ?string $currency;
    protected ?string $taxRate;
    protected ?string $identificationType;
    protected bool $foreigner;
    protected bool $seller;
    protected bool $employee;
    protected bool $provider;
    protected bool $customer;

    public function __construct($data)
    {
        $this->id = $data['id'] ?? null;
        $this->type = $data['type'] ?? null;
        $this->tradeName = $data['trade_name'] ?? null;
        $this->phones = $data['phones'] ?? null;
        $this->identificationCard = $data['identification_card'] ?? null;
        $this->ruc = $data['ruc'] ?? null;
        $this->businessName = $data['business_name'] ?? null;
        $this->address = $data['address'] ?? null;
        $this->email = $data['email'] ?? null;
        $this->discountPercentage = $data['discount_percentage'] ?? 0;
        $this->currency = $data['currency'] ?? null;
        $this->taxRate = $data['tax_rate'] ?? null;
        $this->identificationType = $data['identification_type'] ?? null;
        $this->foreigner = $data['foreigner'] ?? false;
        $this->seller = $data['seller'] ?? false;
        $this->employee = $data['employee'] ?? false;
        $this->provider = $data['provider'] ?? false;
        $this->customer = $data['customer'] ?? true;
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function getTradeName(): ?string
    {
        return $this->tradeName;
    }

    /**
     * @return string|null
     */
    public function getPhones(): ?string
    {
        return $this->phones;
    }

    /**
     * @return string|null
     */
    public function getIdentificationCard(): ?string
    {
        return $this->identificationCard;
    }

    /**
     * @return string|null
     */
    public function getRuc(): ?string
    {
        return $this->ruc;
    }

    /**
     * @return string|null
     */
    public function getBusinessName(): ?string
    {
        return $this->businessName;
    }

    /**
     * @return string|null
     */
    public function getAddress(): ?string
    {
        return $this->address;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @return string|null
     */
    public function getDiscountPercentage(): ?string
    {
        return $this->discountPercentage;
    }

    /**
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @return string|null
     */
    public function getTaxRate(): ?string
    {
        return $this->taxRate;
    }

    /**
     * @return string|null
     */
    public function getIdentificationType(): ?string
    {
        return $this->identificationType;
    }

    /**
     * @return bool
     */
    public function isForeigner(): bool
    {
        return $this->foreigner;
    }

    /**
     * @return bool
     */
    public function isSeller(): bool
    {
        return $this->seller;
    }

    /**
     * @return bool
     */
    public function isEmployee(): bool
    {
        return $this->employee;
    }

    /**
     * @return bool
     */
    public function isProvider(): bool
    {
        return $this->provider;
    }

    /**
     * @return bool
     */
    public function isCustomer(): bool
    {
        return $this->customer;
    }
}
