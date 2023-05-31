<?php

namespace App\Clients;

use App\Interfaces\ICrmClient;
use Illuminate\Support\Facades\Storage;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\oauth\exception\ZohoOAuthException;
use zcrmsdk\oauth\ZohoOAuth;

class ZohoClient implements ICrmClient
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
            $clientId = env('APP_DEBUG') ? env('ZOHO_API_PAYMENTS_TEST_CLIENT_ID') : env('ZOHO_API_PAYMENTS_PROD_CLIENT_ID');
            $secret = env('APP_DEBUG') ? env('ZOHO_API_PAYMENTS_TEST_CLIENT_SECRECT') : env('ZOHO_API_PAYMENTS_PROD_CLIENT_SECRECT');
            $uri = env('APP_DEBUG') ? 'https://www.zoho.com' : 'https://www.oceanomedicina.com.ar';
            \Log::info("Logging to zoho client", [$clientId, $secret, $uri]);
            ZCRMRestClient::initialize([
                "client_id" => $clientId,
                "client_secret" => $secret,
                "redirect_uri" => $uri,
                "token_persistence_path" => Storage::path("zoho"),
                "persistence_handler_class" => "ZohoOAuthPersistenceByFile",
                "currentUserEmail" => env('APP_DEBUG') ? 'copyzoho.custom@gmail.com' : 'sistemas@oceano.com.ar',
                //'copyzoho.custom@gmail.com',
                "accounts_url" => 'https://accounts.zoho.com',
                "access_type" => "offline"
            ]);
            $oAuthClient = ZohoOAuth::getClientInstance();
            $refreshToken = env('APP_DEBUG') ? env('ZOHO_API_PAYMENTS_TEST_REFRESH_TOKEN') : env('ZOHO_API_PAYMENTS_PROD_REFRESH_TOKEN');
            $userIdentifier = env('APP_DEBUG') ? 'copyzoho.custom@gmail.com' : 'sistemas@oceano.com.ar';
            $oAuthTokens = $oAuthClient->generateAccessTokenFromRefreshToken($refreshToken, $userIdentifier);

            return self::getClient();
        }
    }
}
