<?php

namespace App\Http\Controllers\PaymentsMsk;

use App\Http\Controllers\Controller;
use App\Services\PaymentsMsk\CreatePaymentsMskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreatePaymentMskController extends Controller
{
    /**
     * @var CreatePaymentsMskService
     */
    private CreatePaymentsMskService $service;

    public function __construct(
        CreatePaymentsMskService $service
    ) {
        $this->service = $service;
    }

    public function create(Request $request): JsonResponse
    {
        try {
            $response = $this->service->create($request->all());
            return response()->json([
                "data" => $response
            ]);
        } catch (\Exception $e) {
            return response()->json([
                "error" => $e->getMessage()
            ], 400);
        }
    }
}
