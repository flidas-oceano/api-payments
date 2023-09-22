<?php

namespace App\Http\Controllers\Contifico;

use App\Dtos\Contifico\ContificoInvoiceChargeDto;
use App\Dtos\Contifico\ContificoUserDto;
use App\Helpers\Responser;
use App\Http\Controllers\Controller;
use App\Services\Contifico\ReadUser;
use App\Services\Contifico\WriteCharge;
use App\Services\Contifico\WriteUser;
use App\Validations\Contifico\ContificoChargeValidator;
use App\Validations\Contifico\ContificoUserValidator;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContificoChargeController extends Controller
{
    private WriteCharge $writeCharge;

    public function __construct(
        WriteCharge $writeCharge
    ) {
        $this->writeCharge = $writeCharge;
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $requested = $request->all();
            ContificoChargeValidator::create($requested);
            $contificoDto = new ContificoInvoiceChargeDto($requested);
            $data = $this->writeCharge->save($contificoDto);
            $code = 201;

            return Responser::success($data, $code);
        } catch (\Exception | GuzzleException $e) {
            return Responser::error($e);
        }
    }
}
