<?php

namespace App\Dtos;
use App\Interfaces\ISearchDto;

class StripePaymentSearchDto implements ISearchDto
{
    protected ?array $results;

    public function __construct($data)
    {
        $results = [];
        foreach ($data as $item) {
            $results[] = new StripePaymentDto($item);
        }
        $this->results = $results;
    }

    public function getResults(): ?array
    {
        return $this->results;
    }
}
