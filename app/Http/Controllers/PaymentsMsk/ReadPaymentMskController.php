<?php

namespace App\Http\Controllers\PaymentsMsk;

use App\Http\Controllers\Controller;
use App\Services\PaymentsMsk\CreatePaymentsMskService;
use App\Services\PaymentsMsk\ListPaymentsMskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadPaymentMskController extends Controller
{
    private ListPaymentsMskService $service;

    public function __construct(
        ListPaymentsMskService $service
    ) {
        $this->service = $service;
    }

    public function list(Request $request): JsonResponse
    {
        try {
            $response = $this->service->findBy($request->all());

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
