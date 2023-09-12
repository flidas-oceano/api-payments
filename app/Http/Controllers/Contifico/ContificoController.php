<?php

namespace App\Http\Controllers\Contifico;

use App\Dtos\Contifico\ContificoUserDto;
use App\Http\Controllers\Controller;
use App\Services\Contifico\ReadUser;
use App\Services\Contifico\WriteUser;
use App\Validations\ContificoValidator;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContificoController extends Controller
{
    private ReadUser $readUser;
    private WriteUser $writeUser;

    public function __construct(
        ReadUser $readUser,
        WriteUser $writeUser
    ) {
        $this->readUser = $readUser;
        $this->writeUser = $writeUser;
    }

    public function createUser(Request $request): JsonResponse
    {
        try {
            $requested = $request->all();
            ContificoValidator::createUser($requested);
            $response = $this->readUser->findBy($requested);
            if (sizeof($response) == 1) {
                $data = $response;
            } else {
                $contificoDto = new ContificoUserDto($requested);
                $data = $this->writeUser->save($contificoDto);
            }

            return response()->json([
                "data" => $data
            ]);
        } catch (\Exception | GuzzleException $e) {
            return response()->json([
                "error" => $e->getMessage()
            ], 400);
        }
    }
}
