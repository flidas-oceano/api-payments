<?php

namespace App\Clients;

use App\Interfaces\IClient;
use Illuminate\Support\Facades\Storage;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\oauth\exception\ZohoOAuthException;
use zcrmsdk\oauth\ZohoOAuth;

class ZohoClient implements IClient
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
                "client_id" => env('ZOHO_API_PAYMENTS_MSK_CLIENT_ID'),
                "client_secret" => env('ZOHO_API_PAYMENTS_MSK_CLIENT_SECRECT'),
                "redirect_uri" => 'https://www.msklatam.com',
                "token_persistence_path" => Storage::path("zoho"),
                "persistence_handler_class" => "ZohoOAuthPersistenceByFile",
                "currentUserEmail" => 'integraciones@msklatam.com',
                "accounts_url" => 'https://accounts.zoho.com',
                "access_type" => "offline"
            ]);

            $oAuthClient = ZohoOAuth::getClientInstance();
            $refreshToken = env('ZOHO_API_PAYMENTS_MSK_REFRESH_TOKEN');
            $userIdentifier = 'integraciones@msklatam.com';
            $oAuthTokens = $oAuthClient->generateAccessTokenFromRefreshToken($refreshToken, $userIdentifier);

            return self::getClient();
        }
    }
}
