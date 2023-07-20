<?php

namespace App\Clients;

use App\Interfaces\IClient;
use Illuminate\Support\Facades\Storage;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\oauth\exception\ZohoOAuthException;
use zcrmsdk\oauth\ZohoOAuth;

class ZohoMskClient implements IClient
{
    /**
     * @throws ZohoOAuthException
     */
    public function getClient(): ZCRMRestClient
    {
        try {
            $instance = ZCRMRestClient::getInstance();
            $instance->getAllModules();

            return $instance;
        } catch (\Exception $e) {
            ZCRMRestClient::initialize([
                "client_id" => '1000.3RG4V6380Z6J0QJ8VGXO2V0PBMELGK',
                "client_secret" => '81d8708344811e068588c0bf635a186f195da8bedb',
                "redirect_uri" => 'https://www.msklatam.com',
                "token_persistence_path" => Storage::path("zohomsk"),
                "persistence_handler_class" => "ZohoOAuthPersistenceByFile",
                "currentUserEmail" => 'integraciones@msklatam.com',
                "accounts_url" => 'https://accounts.zoho.com',
                "access_type" => "offline"
            ]);
            $oAuthClient = ZohoOAuth::getClientInstance();

            $refreshToken = "1000.21d634af0695ff7e2ea1c783628d3ead.a5ec4489bb6cdb31c8ac2f5435f94923";
            $userIdentifier = "integraciones@msklatam.com";
            $oAuthTokens = $oAuthClient->generateAccessTokenFromRefreshToken($refreshToken, $userIdentifier);

            return self::getClient();
        }
    }
}
