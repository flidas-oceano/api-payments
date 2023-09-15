<?php

namespace App\Dtos\Contifico;

class ContificoInvoiceDto
{
    protected ?string $id;
    public function __construct($data)
    {
        $this->id = $data['id'] ?? null;

    }
}
