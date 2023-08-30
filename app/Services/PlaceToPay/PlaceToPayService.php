<?php

namespace App\Services\PlaceToPay;

use App\Models\PlaceToPaySubscription;
use App\Models\PlaceToPayTransaction;
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
    public function pagarCuotaSuscripcionAnticipo(){}
    public function payInstallmentsSubscriptions(){
        PlaceToPaySubscription::where('status', '!=' , 'APPROVED')->get();
    }
    public function pagarCuotaSuscripcion($request, $nro_quote){
        // pagar primer cuota de subscripcion normal, no anticipo
        $payer = [
            "name" => "Facundo",//$request->lead->name
            "surname" => "Brizuela",//$request->lead->username
            "email" => "facundobrizuela@oceano.com.ar",//$request->lead->email
            "document" => "1758859431",//contact->dni,rut,rfc,mui
            "documentType" => "CC",//hardcodeado
        ];
        $payment = [
            "reference" => 'Cuota '.$nro_quote.'-'.$request['reference'],
            "description" => "Prueba pago de cuota subscripcion",
            "amount" => [
            "currency" => $request['currency'],
            "total" => $request['remaining_installments']
            ]
        ];
        $data = [
            "auth" => $this->generateAuthentication(),
            "locale" => "es_CO",
            "payer" => $payer,
            "payment" => $payment,
            "instrument" => [
                "token" => [
                "token" => $request['token_collect_para_el_pago']
                ]
            ],
            "expiration" => $this->getDateExpiration(),
            "returnUrl" => "https://dnetix.co/p2p/client",
            // "ipAddress" => $request->ip(), // Usar la dirección IP del cliente
            // "userAgent" => $request->header('User-Agent')
        ];

        $response = $this->billSubscription($data);
        // guardas registro primer cuota
        PlaceToPaySubscription::create([
            'transactionId' => $request->id,
            'nro_quote' => $nro_quote,
            'date' => $response['status']['date'],
            'requestId' => $response['requestId'],
            'total' => $response['request']['payment']['amount']['total'],
            'currency' => $response['request']['payment']['amount']['currency'],
            'status' => $response['status']['status'],
        ]);

        //Se realizo el pago exitosamente ?
        return ($response['status']['status'] === 'APPROVED')? true: false;
    }
    public function createInstallments(){
        $requestsSubscription = PlaceToPayTransaction::where(['type' => 'requestSubscription','status' => 'APPROVED'])->get();
        foreach($requestsSubscription as $request){

            //ver si ya se crearon las cuotas
            if (!(count($request->subscriptions) === $request->quotes)) {
                //No estan creadas todas las cuotas de la suscripcion

                //empiezo pagando la primer cuota

                $success = false;
                //es anticipo ?
                if($request->first_installment !== null)
                    $success = $this->pagarCuotaSuscripcionAnticipo($request);
                else
                    $success = $this->pagarCuotaSuscripcion($request, 1);

                // creas todas las cuotas restantes, si hay
                if($success){
                    // pagar cuotas
                    if($request->quotes > 1){
                       for ($i = 2; $i <= $request->quotes; $i++) {
                            PlaceToPaySubscription::create([
                               'transactionId' => $request->id,
                               'nro_quote' => $i,
                               // 'date' => $response['status']['date'],
                               // 'requestId' => $response['status']['status'],
                               'total' => $request->remaining_installments,
                               'currency' => $request->currency,
                               // 'type' => 'subscription',
                            ]);
                        }
                    }
                }

                // pagar cuotas

            }

            // pagar cuotas
            // for ($i = 1; $i <= $request->quotes; $i++) {
            //     PlaceToPaySubscription::create([
            //         'requestIdFather' => $request->requestId,
            //         // 'requestId' => ,
            //         // 'total' => ,
            //         // 'currency' => ,
            //         // 'suscription' => ,
            //         // 'suscription' => ,
            //         // 'payment date' => ,
            //     ]);
            // }
        }
    }
    public function reduceUrl($url){
        // $url = "https://checkout-test.placetopay.ec/spa/session/674726/64b7bae0b1abda60fc3d0b29d30493e8";
        $parts = explode("/", $url); // Dividir la URL en partes usando "/" como separador
        $ultimo_dato = end($parts); // Tomar el último elemento de las partes
        return $ultimo_dato;
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
