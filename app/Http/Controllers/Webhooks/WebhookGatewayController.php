<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Webhooks\CreateSubPaymentsRegistryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookGatewayController extends Controller
{
    /**
     * @var CreateSubPaymentsRegistryService
     */
    private CreateSubPaymentsRegistryService $service;

    public function __construct(
        CreateSubPaymentsRegistryService $service
    ) {
        $this->service = $service;
    }

    public function newWebhook(Request $request): JsonResponse
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
