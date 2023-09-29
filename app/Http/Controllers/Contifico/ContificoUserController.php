<?php

namespace App\Http\Controllers\Contifico;

use App\Dtos\Contifico\ContificoUserDto;
use App\Helpers\Responser;
use App\Http\Controllers\Controller;
use App\Services\Contifico\ReadUser;
use App\Services\Contifico\WriteUser;
use App\Validations\Contifico\ContificoUserValidator;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContificoUserController extends Controller
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

    public function store(Request $request): JsonResponse
    {
        try {
            $requested = $request->all();
            \Log::info("ContificoUserController", ['createUser', $requested]);
            ContificoUserValidator::createUser($requested);
            $response = $this->readUser->findBy($requested);
            if (sizeof($response) == 1) {
                $data = $response;
                $code = 200;
            } else {
                $contificoDto = new ContificoUserDto($requested);
                $data = $this->writeUser->save($contificoDto);
                $code = 201;
            }

            return Responser::success($data, $code);
        } catch (\Exception | GuzzleException $e) {
            return Responser::error($e);
        }
    }

    public function getUser($userId): JsonResponse
    {
        try {
            $response = $this->readUser->findById($userId);

            return Responser::success($response);
        } catch (\Exception | GuzzleException $e) {
            return Responser::error($e);
        }
    }
}
