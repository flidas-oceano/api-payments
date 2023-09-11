<?php

namespace App\Http\Controllers\Contifico;

use App\Http\Controllers\Controller;
use App\Services\Contifico\ReadUser;
use App\Services\Webhooks\CreateSubPaymentsRegistryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContificoController extends Controller
{
    private ReadUser $readUser;

    public function __construct(
        ReadUser $readUser
    ) {
        $this->readUser = $readUser;
    }

    public function createUser(Request $request): JsonResponse
    {
        try {
            $request = $request->all();
            $response = $this->readUser->findBy($request);

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
