<?php

namespace App\Services\PlaceToPay;

use Illuminate\Support\Facades\Http;

class PlaceToPayService
{
    private $login_pu;
    private $secret_pu;
    private $login_su;
    private $secret_su;

    public function __construct() {
        $this->login_pu = env("REACT_APP_PTP_LOGIN_PU");
        $this->secret_pu = env("REACT_APP_PTP_SECRECT_PU");
        $this->login_su = env("REACT_APP_PTP_LOGIN_SU");
        $this->secret_su = env("REACT_APP_PTP_SECRECT_SU");
    }
    public function getDateExpiration()
    {
          // Obtener la fecha y hora actual
            $currentDateTime = new \DateTime();
            // Sumar 24 horas a la fecha actual
            $currentDateTime->add(new \DateInterval('PT24H'));
            // Formatear la fecha para que coincida con el formato ISO 8601
            $seed = $currentDateTime->format('Y-m-d\TH:i:sP');

            return $seed;
    }
    public function getAuth(){
        // Generar autenticación
        $auth = $this->generateAuthentication();

        return response()->json($auth);
    }
    public function generateAuthentication()
    {
            $login = $this->login_pu;
            $secretKey = $this->secret_pu;
            $seed = date('c');
            $rawNonce = rand();

            $tranKey = base64_encode(hash('sha256', $rawNonce.$seed.$secretKey, true));
            $nonce = base64_encode($rawNonce);

            return [
                  "login" => $login,
                  "tranKey" => $tranKey,
                  "nonce" => $nonce,
                  "seed" => $seed,
            ];
    }
    public function create($data){
        $url = "https://checkout-test.placetopay.ec/api/session";
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($url, $data)->json();

        return $response;
    }
    public function getByRequestId($requestId)
    {
        $url = "https://checkout-test.placetopay.ec/api/session/".$requestId;
        $data = [
            "auth" => $this->generateAuthentication(),
        ];

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($url, $data)->json();

        return $response;
    }
    public function billSubscription($data){
        $url = "https://checkout-test.placetopay.ec/api/collect";
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($url, $data)->json();

        return $response;
    }
}
