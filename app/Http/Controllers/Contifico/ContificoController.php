<?php

namespace App\Http\Controllers\Contifico;

use App\Dtos\Contifico\ContificoUserDto;
use App\Helpers\Responser;
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

    public function store(Request $request): JsonResponse
    {
        try {
            $requested = $request->all();
            \Log::info("ContificoController", ['createUser', $requested]);
            ContificoValidator::createUser($requested);
            $response = $this->readUser->findBy($requested);
            if (sizeof($response) == 1) {
                $data = $response;
            } else {
                $contificoDto = new ContificoUserDto($requested);
                $data = $this->writeUser->save($contificoDto);
            }

            return Responser::success($data);
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
