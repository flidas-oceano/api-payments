<?php

namespace App\Http\Controllers\Contifico;

use App\Dtos\Contifico\ContificoInvoiceDto;
use App\Helpers\Responser;
use App\Http\Controllers\Controller;
use App\Services\Contifico\ReadInvoice;
use App\Services\Contifico\WriteInvoice;
use App\Validations\Contifico\ContificoInvoiceValidator;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContificoInvoiceController extends Controller
{
    private ReadInvoice $readInvoice;
    private WriteInvoice $writeInvoice;

    public function __construct(
        ReadInvoice $readInvoice,
        WriteInvoice $writeInvoice
    ) {
        $this->readInvoice = $readInvoice;
        $this->writeInvoice = $writeInvoice;
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $requested = $request->all();
            ContificoInvoiceValidator::create($requested);
            $contificoDto = new ContificoInvoiceDto($requested);
            $data = $this->writeInvoice->save($contificoDto);
            $this->writeInvoice->send2SRI($contificoDto);

            return Responser::success($data, 201);
        } catch (\Exception | GuzzleException $e) {
            return Responser::error($e);
        }
    }

    public function getInvoice($invoiceId): JsonResponse
    {
        try {
            $response = $this->readInvoice->findById($invoiceId);

            return Responser::success($response);
        } catch (\Exception | GuzzleException $e) {
            return Responser::error($e);
        }
    }
}
