<?php

namespace App\Http\Controllers\Webhooks;

use App\Clients\ZohoMskClient;
use App\Http\Controllers\Controller;
use App\Interfaces\ISaveWebhookCrmService;
use App\Services\Webhooks\SaveWebhookZohoCrmService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
class CrmOrderSalesStep5ChargeDetailsController extends Controller
{
    private ISaveWebhookCrmService $service;

    public function __construct(
        ZohoMskClient $client
    ) {
        $this->service = new SaveWebhookZohoCrmService($client);
    }

    public function send2Crm(Request $request): JsonResponse
    {
        try {
            $response = $this->service->saveWebhook2Crm($request->all());

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
