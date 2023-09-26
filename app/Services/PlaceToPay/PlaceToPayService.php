<?php

namespace App\Services\PlaceToPay;

use App\Models\PlaceToPaySubscription;
use App\Models\PlaceToPayTransaction;
use Carbon\Carbon;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use stdClass;

// use App\Services\PlaceToPay\PlaceToPayService; $placeToPayService = new PlaceToPayService();
class PlaceToPayService
{
    private $login_pu;
    private $secret_pu;
    private $login_su;
    private $secret_su;

    public function __construct()
    {
        $this->login_pu = env("REACT_APP_PTP_LOGIN_PU");
        $this->secret_pu = env("REACT_APP_PTP_SECRECT_PU");
        $this->login_su = env("REACT_APP_PTP_LOGIN_SU");
        $this->secret_su = env("REACT_APP_PTP_SECRECT_SU");
    }
    public function getDateExpiration()
    {
        // Obtener la fecha y hora actual
        $currentDateTime = new \DateTime();
        // Sumar 30 minutos a la fecha actual
        $currentDateTime->add(new \DateInterval('PT30M'));
        // Formatear la fecha para que coincida con el formato ISO 8601
        $expirationDate = $currentDateTime->format('Y-m-d\TH:i:sP');

        return $expirationDate;
    }
    public function pagarCuotaSuscripcionAnticipo()
    {
    }
    public function payInstallmentsSubscriptions()
    {
        PlaceToPaySubscription::where('status', '!=', 'APPROVED')->get();
    }
    public function pagarCuotaSuscripcion($request, $nro_quote)
    {
        $requestSubscriptionById = $this->getByRequestId($request['requestId'], $cron = false,$isSubscription = true);

        $transaccion = PlaceToPayTransaction::find($request->id);

        // pagar primer cuota de subscripcion normal, no anticipo
        $payer = [
            "name" => $requestSubscriptionById['request']['payer']['name'],
            //$request->lead->name
            "surname" => $requestSubscriptionById['request']['payer']['surname'],
            //$request->lead->username
            "email" => $requestSubscriptionById['request']['payer']['email'],
            //$request->lead->email
            "document" => $requestSubscriptionById['request']['payer']['document'],
            //contact->dni,rut,rfc,mui
            "documentType" => $requestSubscriptionById['request']['payer']['documentType'],
            "mobile" => $requestSubscriptionById['request']['payer']['mobile'],
            // "address" => [ //domicilio
            //     // "country" => $request['country'],
            //     // "state" => $request['state'],
            //     // "city" => $request['city'],
            //     // "postalCode" => $request['postalCode'],
            //     "street" => $requestSubscriptionById['request']['payer']['address']['street'],
            //     // "phone" => $request['phone'],//+573214445566
            // ]
        ];
        // $requestsTransaction = PlaceToPayTransaction::where(['requestId' => $request['requestId']['requestId']])->get()->first();

        $payment = [
            "reference" => $this->getNameReferenceSubscription(1,$requestSubscriptionById['requestId'],$request['reference']),
            "description" => "",
            "amount" => [
                "currency" => $request['currency'],
                "total" => $transaccion->first_installment ?? $request['remaining_installments']
            ]
        ];
        $data = [
            "auth" => $this->generateAuthentication($isSubscription = true),
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
        $response = $this->billSubscription($data, $cron = false);
        if(($response['payment'][0]['status']['status'] ?? null) === 'APPROVED'){
            // Actualizo el transactions, campo: installments_paid
            PlaceToPayTransaction::find($request->id)->update(['installments_paid' => DB::raw('COALESCE(installments_paid, 0) + 1')]);
            $session = PlaceToPayTransaction::find($request->id); //
            if($session->paymentLinks()->first() !== null){
                $session->paymentLinks()->first()->update(['status' => 'Contrato Efectivo']);
            }
        }

        $newsubscription = [
            'transactionId' => $request->id,
            'nro_quote' => $nro_quote,
            'date' => $response['status']['date'],
            'requestId' => $response['requestId'],
            'total' => $response['request']['payment']['amount']['total'],
            'currency' => $response['request']['payment']['amount']['currency'],
            'status' => $response['status']['status'],
            'date_to_pay' => $response['status']['date'],

            'reason' => $response['status']['reason'],
            'message' => $response['status']['message'],
            'authorization' => $response['payment'][0]['authorization'] ?? null, //TODO: siempre lo veo como : 999999
            'reference' => $response['payment'][0]['reference'] ?? null,
            // 'type' => , //TODO: me parece que es mejor borrarlo de la tabla. O usarla para diferenciar: subscription, advancedInstallment
            // 'expiration_date' => , //TODO: definir cuando se espera que expire una cuota.
        ];
        $firstPaySubscription = PlaceToPaySubscription::create($newsubscription);
        // guardas registro primer cuota

        return [
            "firstPaySubscription" => $firstPaySubscription,
            "response" => $response,
            "data" => $data,
        ];
    }
    public function payFirstQuoteCreateRestQuotesByRequestId($requestIdRequestSubscription)
    {
        $requestsSubscription = PlaceToPayTransaction::where(['requestId' => $requestIdRequestSubscription])->get()->first();

        $subscription = $requestsSubscription->subscriptions->first();
        if( ($subscription->status ?? null) === 'PENDING' ){
            //Actualizar la primer cuota que pasa de PENDING a APPROVED
            $subscriptionByRequestId = $this->getByRequestId($subscription->requestId, $cron = false,$isSubscription = true);
            if ( ($subscriptionByRequestId['payment'][0]['status']['status']??null) === 'APPROVED' ) {

                    // $result = $this->pagarCuotaSuscripcion($requestsSubscription, 1);
                    $requestSubscriptionById = $this->getByRequestId($requestsSubscription['requestId'], $cron = false,$isSubscription = true);

                    if(($subscriptionByRequestId['payment'][0]['status']['status'] ?? null) === 'APPROVED'){
                        // Actualizo el transactions, campo: installments_paid
                        PlaceToPayTransaction::find($requestsSubscription->id)->update(['installments_paid' => DB::raw('COALESCE(installments_paid, 0) + 1')]);
                        $session = PlaceToPayTransaction::find($requestsSubscription->id); //
                        if($session->paymentLinks()->first() !== null){
                            $session->paymentLinks()->first()->update(['status' => 'Contrato Efectivo']);
                        }
                    }
                    $newsubscription = [
                        'transactionId' => $requestsSubscription->id,
                        'date' => $subscriptionByRequestId['status']['date'],
                        'status' => $subscriptionByRequestId['status']['status'],
                        'reason' => $subscriptionByRequestId['status']['reason'],
                        'message' => $subscriptionByRequestId['status']['message'],
                        'authorization' => $subscriptionByRequestId['payment'][0]['authorization'] ?? null, //TODO: siempre lo veo como : 999999
                        'reference' => $subscriptionByRequestId['payment'][0]['reference'] ?? null,
                        // 'type' => , //TODO: me parece que es mejor borrarlo de la tabla. O usarla para diferenciar: subscription, advancedInstallment
                        // 'expiration_date' => , //TODO: definir cuando se espera que expire una cuota.
                    ];
                    $firstPaySubscription = PlaceToPaySubscription::where(['id' => $subscription->id])
                        ->update($newsubscription);
                    $newsubscription = PlaceToPaySubscription::find($subscription->id);
                    // guardas registro primer cuota

                    $result = [
                        "firstPaySubscription" => $newsubscription,
                        "response" => $requestSubscriptionById,
                        // "data" => $data,
                    ];

                    // creas todas las cuotas restantes, si hay
                    if (($result['response']['status']['status']??null) === 'APPROVED') {
                        // $responseUpdateZohoPlaceToPay = $this->zohoController->updateZohoPlaceToPay($result,$requestIdRequestSubscription);
                        $this->createRemainingInstallments($result,$requestsSubscription);
                    }

                    return $result;
            }
        }else{
            // Crear la primer cuota directametne
             if (!(count($requestsSubscription->subscriptions) === $requestsSubscription->quotes)) {
                //No estan creadas todas las cuotas de la suscripcion

                //empiezo pagando la primer cuota
                $result = $this->pagarCuotaSuscripcion($requestsSubscription, 1);

                if (($result['response']['status']['status']??null) === 'REJECTED') {
                    if( !$this->isRejectedTokenTransaction($requestsSubscription) ){
                        // Marca como invalido el token
                        $requestsSubscription->update([
                            'token_collect_para_el_pago' => 'CARD_REJECTED_'.$requestsSubscription->token_collect_para_el_pago
                        ]);
                    }
                }

                // creas todas las cuotas restantes, si hay
                if (($result['response']['status']['status']??null) === 'APPROVED') {
                    // $responseUpdateZohoPlaceToPay = $this->zohoController->updateZohoPlaceToPay($result,$requestIdRequestSubscription);
                    $this->createRemainingInstallments($result,$requestsSubscription);
                }
                return $result;
            }
        }
    }
    public function createRemainingInstallments($result,$requestsSubscription){

        // // crear cuotas
        if ($requestsSubscription->quotes > 1) {
            $dateParsedPaidFirstInstallment = date_parse($result['firstPaySubscription']['date']);
            // $dateParsedPaidFirstInstallment = date_parse("2023-01-30T18:38:53.000000Z"); // TODO: Se puede usar esto para probar unas fecha de cobro especifica

            //Obtener
            $datesToPay = $this->getDatesToPay($dateParsedPaidFirstInstallment,$requestsSubscription->quotes);

            for ($i = 2; $i <= $requestsSubscription->quotes; $i++) {

                PlaceToPaySubscription::create([
                    'transactionId' => $requestsSubscription->id,
                    'nro_quote' => $i,
                    // 'date' => $response['status']['date'],
                    // 'requestId' => $response['status']['status'],
                    'total' => $requestsSubscription->remaining_installments,
                    'currency' => $requestsSubscription->currency,
                    'date_to_pay' => date_format($this->dateToPay(
                        $datesToPay[$i - 2]['year'],
                        $datesToPay[$i - 2]['month'],
                        $dateParsedPaidFirstInstallment['day']
                    ), 'Y-m-d H:i:s'),
                    // 'type' => 'subscription',
                ]);
            }
        }
    }
    public function updateStatusSessionSubscription($SO){
        // $lastRequestSessionDB = PlaceToPayTransaction::where('reference', 'LIKE', '%' . 'sadasd' . '%')->orderBy('created_at', 'desc')->first();
        $lastRequestSessionDB = PlaceToPayTransaction::where('reference', 'LIKE', '%' . $SO . '%')
                ->orderBy('created_at', 'desc')
                ->first();
        if($lastRequestSessionDB !== null){
            $sessionByRequestId =  $this->getByRequestId($lastRequestSessionDB->requestId, $cron = false,$isSubscription = true);
            if (isset($sessionByRequestId['status']['status'])) {
                $placeToPayTransaction = PlaceToPayTransaction::where([ 'requestId' => $sessionByRequestId['requestId'] ])
                    ->update([
                        'status' => $sessionByRequestId['status']['status'],
                        'reason' => $sessionByRequestId['status']['reason'],
                        'message' => $sessionByRequestId['status']['message'],
                        'date' => $sessionByRequestId['status']['date'],
                    ]);
            }
        }
    }
    //Utils

    public function reduceUrl($url)
    {
        // $url = "https://checkout-test.placetopay.ec/spa/session/674726/64b7bae0b1abda60fc3d0b29d30493e8";
        $parts = explode("/", $url); // Dividir la URL en partes usando "/" como separador
        $ultimo_dato = end($parts); // Tomar el último elemento de las partes
        return $ultimo_dato;
    }
    public function getAuth()
    {
        // Generar autenticación
        $auth = $this->generateAuthentication();

        return response()->json($auth);
    }
    public function generateAuthentication($isSubscription = false)
    {
        if($isSubscription){
            $login = $this->login_su;
            $secretKey = $this->secret_su;
        }else{
            $login = $this->login_pu;
            $secretKey = $this->secret_pu;
        }

        $seed = date('c');
        $rawNonce = rand();

        $tranKey = base64_encode(hash('sha256', $rawNonce . $seed . $secretKey, true));
        $nonce = base64_encode($rawNonce);

        return [
            "login" => $login,
            "tranKey" => $tranKey,
            "nonce" => $nonce,
            "seed" => $seed,
        ];
    }
    public function isResponseValid($response,$data = null, $cron = false)
    {
        // Verificar si la respuesta indica un fallo
        if (isset($response['status']['status']) && $response['status']['status'] === 'FAILED') {
            $errorReason = $response['status']['reason'];
            $errorMessage = $response['status']['message'];
            $errorDate = $response['status']['date'];

            if($cron && $data != null){//Esto esta porque la regla diaria de los pagos necesita que no rompa, pero si logear que hubo un error en el intento de pago
                $dataAsString = json_encode($data);
                // Log::channel('placetopay')->info("Payment request failed: Reason: $errorReason, Message: $errorMessage, Date: $errorDate, Data: $dataAsString");
            }
            if(!$cron){
                throw new Exception("Payment request failed: Reason: $errorReason, Message: $errorMessage, Date: $errorDate");
            }
        }
    }
    public function getDatesToPay($dateParsedPaidFirstInstallment, $quotes){
        $datesToPay = [];
        array_push($datesToPay, $dateParsedPaidFirstInstallment);
        for ($i = 2; $i <= $quotes; $i++) {
            array_push(
                $datesToPay,
                date_parse(
                    $this->dateToPay(
                        $datesToPay[$i - 2]['year'],
                        $datesToPay[$i - 2]['month'],
                        $dateParsedPaidFirstInstallment['day']
                    )
                )
            );
        }
        return $datesToPay;
    }
    public function dateToPay($año,$mes,$diaCobroPrimerCuota){

        //tinker
        // use App\Services\PlaceToPay\PlaceToPayService;
        // $placeToPayService = new PlaceToPayService();
        // $proximaCuota = $placeToPayService->dateToPay(2023,1,30);

        // Verifica si el día es válido (entre 1 y 31)
        if ($diaCobroPrimerCuota < 1 || $diaCobroPrimerCuota > 31) {
          // Maneja un mensaje de error o toma alguna acción apropiada
            return "Día inválido";
        }

        $cuotaAnterior = new stdClass();
        // Construir la fecha con el día 1 y el mes y año variables

        $fecha = Carbon::create($año, $mes, 1);
        $cuotaAnterior->fechaCobro = $fecha;
        // Obtener el día de la fecha
        // $cuotaAnterior->diaCobro = (int) $cuotaAnterior->fechaCobro->format('d');
        //cuantos dias faltan para que termine el mes
        $cuotaAnterior->ultimoDiaDelMes = (int) $cuotaAnterior->fechaCobro->format('t');
        //dias que faltan para que termine el mes
        $cuotaAnterior->diasRestantes = $cuotaAnterior->ultimoDiaDelMes - (int) $cuotaAnterior->fechaCobro->format('j');
        //Conseguir el mes siguiente.
        // Suma los días restantes + 1 a la fecha actual
        $cuotaAnterior->diasRestantes++;

        $mesSiguiente = new stdClass();
        $mesSiguiente->primerDia = $cuotaAnterior->fechaCobro->modify("+{$cuotaAnterior->diasRestantes} days");
        $mesSiguiente->ultimoDiaDelMes = (int) $mesSiguiente->primerDia->format('t');

        if ($diaCobroPrimerCuota <= 28) {
            // Modificar el día a "x"
            $mesSiguiente->fechaCobro = $mesSiguiente->primerDia->setDate(
                $mesSiguiente->primerDia->format('Y'),
                $mesSiguiente->primerDia->format('m'),
                $diaCobroPrimerCuota
            );
        }
        if ($diaCobroPrimerCuota > 28 && $mesSiguiente->ultimoDiaDelMes < $diaCobroPrimerCuota ) {
            $mesSiguiente->fechaCobro = $mesSiguiente->primerDia->setDate(
                $mesSiguiente->primerDia->format('Y'),
                $mesSiguiente->primerDia->format('m'),
                $mesSiguiente->ultimoDiaDelMes
            );
        }
        if ($diaCobroPrimerCuota > 28 && $mesSiguiente->ultimoDiaDelMes >= $diaCobroPrimerCuota ) {
            $mesSiguiente->fechaCobro = $mesSiguiente->primerDia->setDate(
                $mesSiguiente->primerDia->format('Y'),
                $mesSiguiente->primerDia->format('m'),
                $diaCobroPrimerCuota
            );
        }

        return $mesSiguiente->fechaCobro;
    }

    // $placeToPayService->getNameReferenceSubscription(1,680007,'2000339000617515006'); // Llama al método que deseas ejecutar
    // $placeToPayService->getNameReferenceSubscription(1,680002,'2000339000617515006'); // Llama al método que deseas ejecutar
    public function getNameReferenceSubscription($nroQuote,$requestIdSession,$contractId){

        // $requestsSession = PlaceToPayTransaction::where(['requestId' => 680002])->get()->first();
        $requestsSession = PlaceToPayTransaction::where(['requestId' => $requestIdSession])->get()->first();

        // $sessionsRejected = $requestsSession->subscriptions()->where(['status' => 'REJECTED', 'nro_quote' => 1])->get();
        $sessionsRejected = $requestsSession->subscriptions()->where(['status' => 'REJECTED', 'nro_quote' => $nroQuote])->get();

        if(count($sessionsRejected) === 0){
            return $nroQuote.'_'.$contractId;
        }
        if(count($sessionsRejected) > 0){
            return $nroQuote.'_'.$contractId.'_R_'.count($sessionsRejected);
        }
    }
    // $placeToPayService->getNameReferenceSession('2000339000617515006'); // Llama al método que deseas ejecutar
    public function getNameReferenceSession($contractId){

        // $requestsSessionByContractId = PlaceToPayTransaction::where('reference', 'LIKE', '%' . '2000339000617515006' . '%')->get();
        $requestsSessionByContractId = PlaceToPayTransaction::where('reference', 'LIKE', '%' . $contractId . '%')->get();

        if( count($requestsSessionByContractId) === 0){
            return $contractId;
        }
        if( count($requestsSessionByContractId) > 0){
            return $contractId.'_RT_'.count($requestsSessionByContractId);
        }
    }

    public function isRejectedTokenTransaction($requestsTransaction){
        $cardToken = $requestsTransaction->token_collect_para_el_pago;
        $textoBuscado = 'CARD_REJECTED_';
        return str_contains($cardToken, $textoBuscado);
    }

    public function canCreateSession($SO){//Se puede crear una nueva session ?

        // $placeToPayService->getNameReferenceSubscription(1,680007,'2000339000617515006');
        // $requestsSessionDB = PlaceToPayTransaction::where('reference', 'LIKE', '%' . '2000339000617515005' . '%')->get();
        // $requestsSessionDB = PlaceToPayTransaction::where('reference', 'LIKE', '%' . $SO . '%')->get();

        // $lastRequestSessionDB = PlaceToPayTransaction::where('reference', 'LIKE', '%' . $SO . '%')->orderBy('created_at', 'desc')->first();

        // $lastRequestSessionDB = PlaceToPayTransaction::where('reference', 'LIKE', '%' . '2000339000617515006' . '%')->orderBy('created_at', 'desc')->first();
        $lastRequestSessionDB = PlaceToPayTransaction::where('reference', 'LIKE', '%' . $SO . '%')
                ->orderBy('created_at', 'desc')
                ->first();

        if($lastRequestSessionDB !== null){
            //Tengo registros con ese SO

            //VER SI TIENE EN PENDING LA SESSION ANTERIOR.
            if($lastRequestSessionDB->status === 'PENDING'){
                return [
                    'canCreateSession' => false,
                ];
            }

            //VER SI TIENE EN PENDIENTE EL PAGO LA SESSION ANTERIOR.
            if($lastRequestSessionDB->status === 'APPROVED'){
                $subscription = $lastRequestSessionDB->subscriptions()->where(['nro_quote' => 1])->orderBy('created_at', 'desc')->get()->first();
                if($subscription->status == 'PENDING'){
                    return [
                        'canCreateSession' => false,
                    ];
                }

                //SI LA PRIMER CUOTA ESTA PAGADA EXITOSAMENTE NO CREAR UNA NUEVA SESSION.
                if($subscription->status == 'APPROVED'){
                    return [
                        'canCreateSession' => false,
                    ];
                }
            }

            //VER SI TIENE EL CARD TOKEN REJECTED.
            if($lastRequestSessionDB->status === 'APPROVED'){
                $subscription = $lastRequestSessionDB->subscriptions()->where(['nro_quote' => 1])->orderBy('created_at', 'desc')->get()->first();
                if($subscription->status == 'REJECTED'){
                    if( !$this->isRejectedTokenTransaction($lastRequestSessionDB) ){
                        return [
                            'canCreateSession' => false,
                        ];
                    }
                }

            }

            // $sessionSubscriptionGetById = $this->getByRequestId($requestsSessionDB['requestId']);
            // $status = $sessionSubscriptionGetById['status']['status'];

            // if($status == 'APPROVED'){
            //     $requestsTransaction = PlaceToPayTransaction::where(
            //         ['requestId' => $sessionSubscriptionGetById->requetId]
            //     )->get()->first();

            //     //En caso de transaccion APPROVED y suscripcion REJECTED
            //     if(count($requestsTransaction->subscriptions) > 0){
            //         $firstSubscription = $requestsTransaction->subscriptions->first();
            //         if($firstSubscription->status === 'REJECTED'){
            //             if(!$this->isRejectedTokenTransaction($requestsTransaction)){
            //                 $requestsTransaction->update([
            //                     'token_collect_para_el_pago' => 'CARD_REJECTED_'.$requestsTransaction->token_collect_para_el_pago
            //                 ]);
            //             }
            //             // return response()->json([
            //             //     "result" => 'La el primer pago fallo, se rechazo la tarjeta. Cree una nueva suscripcion e ingrese otra tarjeta.',
            //             // ], 400);
            //         }
            //     }
            // }
        }

        return [
            'canCreateSession' => true,
        ];
    }
    // End //Utils

    // URLS
    public function revokeTokenSession($requestIdSession){

        $requestSession = PlaceToPayTransaction::where(['requestId' => $requestIdSession])->get()->first();

        $data =[
            "auth"=> $this->generateAuthentication(),
            "locale" => "es_CO",
            "instrument"=> [
                "token"=> [
                    "token"=> $requestSession->token_collect_para_el_pago
                ]
            ]
        ];
        $url = "https://checkout-test.placetopay.ec/api/instrument/invalidate";
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($url, $data)->json();

        $this->isResponseValid($response);

        return $response;

    }
    public function create($data)
    {
        $url = "https://checkout-test.placetopay.ec/api/session";
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($url, $data)->json();

        $this->isResponseValid($response);

        return $response;
    }

    public function getByRequestId($requestId, $cron = null, $isSubscription = false)
    {
        if ($requestId === null) {
            throw new \InvalidArgumentException("El parámetro 'requestId' es obligatorio.");
        }

        $url = "https://checkout-test.placetopay.ec/api/session/" . $requestId;
        $data = [
            "auth" => $this->generateAuthentication($isSubscription),
        ];

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($url, $data)->json();

        $this->isResponseValid($response,$requestId , $cron);

        return $response;
    }
    public function billSubscription($data,$cron = null)
    {
        if ($data === null) {
            throw new \InvalidArgumentException("El parámetro 'data' es obligatorio.");
        }

        $url = "https://checkout-test.placetopay.ec/api/collect";
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($url, $data)->json();

        $this->isResponseValid($response,$data,$cron);

        return $response;
    }
    ///End URLS

    // Cronologia de cobro
    public function createInstallments()
    {
        $requestsSubscription = PlaceToPayTransaction::where(['type' => 'requestSubscription', 'status' => 'APPROVED'])->get();
        foreach ($requestsSubscription as $request) {

            //ver si ya se crearon las cuotas
            if (!(count($request->subscriptions) === $request->quotes)) {
                //No estan creadas todas las cuotas de la suscripcion

                //empiezo pagando la primer cuota

                $success = false;
                //es anticipo ?
                if ($request->first_installment !== null)
                    $success = $this->pagarCuotaSuscripcionAnticipo($request);
                else
                    $success = $this->pagarCuotaSuscripcion($request, 1);

                // creas todas las cuotas restantes, si hay
                if ($success) {
                    // pagar cuotas
                    if ($request->quotes > 1) {
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

    public function payInstallments(){
        // Log::channel('placetopay')->info('Se ejecuta la regla de payInstallments.');
        // $subscriptions = PlaceToPaySubscription::where('status', '!=', 'APPROVED')->orWhereNull('status')->get();

        $subscriptions = PlaceToPaySubscription::where('status' , null)->get();

        //Si tengo las cuotas al dia, puedo realizar el pago


        $today = Carbon::now();
        foreach ($subscriptions as $subscription) {
            // $subscription = $subscriptions->first()
            // $dateToPay = Carbon::parse($subscription->date_to_pay);
            $dateToPay = Carbon::parse($subscription->date_to_pay); // Convierte la fecha_to_pay a objeto Carbon
            if ($dateToPay->isSameDay($today)) {

                // La fecha_to_pay es igual al día de hoy
                // Realiza la acción que deseas para esta subscripción
                // Por ejemplo, imprime los detalles de la subscripción

                // Preguntar si tiene las cuotas al dia
                // Tomar la cuota de hoy y buscar las anteriores o solo una anterior.
                // verificar que esten todas pagas.

                ///if(!$cuotaAnterior->estaPaga()){
                // Paga normalmente la de este mes
                // }else{
                //  Definir el procedimiento
                //  porque puedo ver las anteriores y o solo la anterior a ver si estan o esta impaga
                //  pagaria todas desde principio a fin al mismo tiempo o escalado renovando la fecha de cobro para cada una ?
                //  pagaria la de este mes y despues me fijo que hago con las demas cuotas impagas o no pago la de este mes hasta que se paguen las demas?
                // }

                //es anticipo ?
                if ($subscription->transaction->first_installment !== null)
                    $result = $this->pagarCuotaSuscripcionAnticipo($subscription->transaction);
                else{
                    // $result = $this->pagarCuotaSuscripcion($subscription->transaction, $subscription->nro_quote);

                    $request = $subscription->transaction;
                    $nro_quote = $subscription->nro_quote;

                    $requestSubscriptionById = $this->getByRequestId($request['requestId']);
                    // pagar primer cuota de subscripcion normal, no anticipo
                    $payer = [
                        "name" => $requestSubscriptionById['request']['payer']['name'],
                        //$request->lead->name
                        "surname" => $requestSubscriptionById['request']['payer']['surname'],
                        //$request->lead->username
                        "email" => $requestSubscriptionById['request']['payer']['email'],
                        //$request->lead->email
                        "document" => $requestSubscriptionById['request']['payer']['document'],
                        //contact->dni,rut,rfc,mui
                        "documentType" => $requestSubscriptionById['request']['payer']['documentType'],
                        "mobile" => $requestSubscriptionById['request']['payer']['mobile'],
                        // "address" => [ //domicilio
                        //     // "country" => $request['country'],
                        //     // "state" => $request['state'],
                        //     // "city" => $request['city'],
                        //     // "postalCode" => $request['postalCode'],
                        //     "street" => $requestSubscriptionById['request']['payer']['address']['street'],
                        //     // "phone" => $request['phone'],//+573214445566
                        // ]
                    ];
                    $payment = [
                        "reference" => $nro_quote . '_' . $request['reference'],
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

                    $response = $this->billSubscription($data,$cron = true);

                    if($response['payment'][0]['status'] ?? null !== 'APPROVED'){
                        // Actualizo el transactions, campo: installments_paid
                        PlaceToPayTransaction::find($request->id)->update(['installments_paid' => DB::raw('COALESCE(installments_paid, 0) + 1')]);
                    }

                    if(($response['status']['status'] ?? null) === 'FAILED'){
                        $paidaySubscription = PlaceToPaySubscription::find($subscription->id)->update([
                            'status' => $response['status']['status'],
                            'reason' => $response['status']['reason'],
                            'message' => $response['status']['message'],
                            'date' => $response['status']['date'],
                        ]);
                    }else{
                        // guardas registro primer cuota
                        $paidaySubscription = PlaceToPaySubscription::find($subscription->id)->update([
                            // 'transactionId' => $request->id,
                            'status' => $response['status']['status'],
                            'reason' => $response['status']['reason'],
                            'message' => $response['status']['message'],
                            'date' => $response['status']['date'],
                            'requestId' => $response['requestId'],

                            'authorization' => $response['payment'][0]['authorization'] ?? null, //TODO: siempre lo veo como : 999999
                            // 'total' => $response['request']['payment']['amount']['total'],
                            // 'currency' => $response['request']['payment']['amount']['currency'],
                            'nro_quote' => $nro_quote,
                            'reference' => $response['payment'][0]['reference'] ?? null,
                            // 'type' => , //TODO: me parece que es mejor borrarlo de la tabla. O usarl para: subscription, advancedInstallment
                            // 'expiration_date' => , //TODO: definir cuando se espera que expire una cuota.
                            'date_to_pay' => $response['status']['date'],
                        ]);

                        // Log::channel('placetopay')->info('Se intento realizar el pago de este id: .'.$paidaySubscription->id);
                    }

                }

                // $this->pagarCuotaSuscripcion(['requestId' => $subscription->transaction->requestId], 2);

                // $result = $placeToPayService->pagarCuotaSuscripcion($subscription->transaction, $subscription->nro_quote);

            }
        }

        // $query = PlaceToPaySubscription::where('status', '!=', 'APPROVED');
        // $sql = $query->toSql();

    }

    public function payIndividualPayment($subscriptionToPay)
    {
        $session = PlaceToPayTransaction::find($subscriptionToPay->transaction->id);
        $paymentData = json_decode($session->paymentData);
        $payment = [
            "reference" => $this->getNameReferenceSubscription($subscriptionToPay->nro_quote,$session->requestId,$session->reference),
            "description" => "",
            "amount" => [
                "currency" => $subscriptionToPay->currency,
                "total" => $subscriptionToPay->total
            ]
        ];
        $data = [
            "auth" => $this->generateAuthentication(),
            "locale" => "es_CO",
            "payer" => $paymentData,
            "payment" => $payment,
            "instrument" => [
                "token" => [
                    "token" => $session->token_collect_para_el_pago
                ]
            ],
            "expiration" => $this->getDateExpiration(),
            "returnUrl" => "https://dnetix.co/p2p/client",
        ];
        $response = $this->billSubscription($data, $cron = true);
        if(($response['payment'][0]['status']['status'] ?? null) === 'APPROVED'){
            // Actualizo el transactions, campo: installments_paid
            PlaceToPayTransaction::find($session->id)->update(['installments_paid' => DB::raw('COALESCE(installments_paid, 0) + 1')]);
        }
        PlaceToPaySubscription::find($subscriptionToPay->id)->update([
            'status' =>         $response['status']['status'],
            'message' =>        $response['status']['message'],
            'reason' =>         $response['status']['reason'],
            'date' =>           $response['status']['date'],
            'reference' =>      $response['payment'][0]['reference'] ?? null,
            'authorization' =>  $response['payment'][0]['authorization'] ?? null, //TODO: siempre lo veo como : 999999
            'requestId' =>      $response['requestId'],

            'currency' =>       $payment['amount']['currency'],
            'total' =>          $payment['amount']['total'],
            // 'type' => , //TODO: me parece que es mejor borrarlo de la tabla. O usarla para diferenciar: subscription, advancedInstallment
        ]);
        $updateSubscription = PlaceToPaySubscription::find($subscriptionToPay->id);

        return [
            "updateSubscription" => $updateSubscription,
            "response" => $response,
            "data" => $data,
        ];
    }

    //Refresca las sessions y las subscriptions de estado PENDING.
    public function refreshPendings(){
        $sessions = PlaceToPayTransaction::whereIn( 'status' , ['OK','PENDING'] )->get();
        foreach($sessions as $session){
            $sessionFromPTP = $this->getByRequestId($session->requestId, $cron = true);
            //Actualizar session
            if (isset($sessionFromPTP['status']['status'])) {
                $updateSession = PlaceToPayTransaction::where( [ 'requestId' => $sessionFromPTP['requestId'] ] )
                    ->update([
                        'status' => $sessionFromPTP['status']['status'],
                        'reason' => $sessionFromPTP['status']['reason'],
                        'message' => $sessionFromPTP['status']['message'],
                        'date' => $sessionFromPTP['status']['date'],
                    ]);
            // //Guardar el cardToken
            if ($sessionFromPTP['status']['status'] === "APPROVED") {
                if (isset($sessionFromPTP['subscription'])) {
                    foreach ($sessionFromPTP['subscription']['instrument'] as $instrument) {
                        if ($instrument['keyword'] === "token") {
                            PlaceToPayTransaction::updateOrCreate(
                                ['requestId' => $sessionFromPTP["requestId"]],
                                [
                                    'token_collect_para_el_pago' => $instrument['value']
                                ]
                            );
                        }
                    }
                }
            }
            //Si pasa a REJECTED cancelar cardToken
            if ($sessionFromPTP['status']['status'] === "REJECTED") {
                if (isset($sessionFromPTP['subscription'])) {
                    foreach ($sessionFromPTP['subscription']['instrument'] as $instrument) {
                        if ($instrument['keyword'] === "token") {
                            PlaceToPayTransaction::updateOrCreate(
                                ['requestId' => $sessionFromPTP["requestId"]],
                                [
                                    'token_collect_para_el_pago' => 'CARD_REJECTED_'.$instrument['value']
                                ]
                            );
                        }
                    }
                }
            }

            }
        }
        $subscriptions = PlaceToPaySubscription::whereIn( 'status' , ['OK','PENDING'] )->get();
        foreach($subscriptions as $subscription){
            $subscriptionFromPTP = $this->getByRequestId($subscription->requestId, $cron = true);
            //Actualizar subscription
            if ( isset($subscriptionFromPTP['status']['status']) ) {
                $updateSubscription = PlaceToPayTransaction::where( [ 'requestId' => $subscriptionFromPTP['requestId'] ] )
                    ->update([
                        'status' => $subscriptionFromPTP['status']['status'],
                        'reason' => $subscriptionFromPTP['status']['reason'],
                        'message' => $subscriptionFromPTP['status']['message'],
                        'date' => $subscriptionFromPTP['status']['date'],
                    ]);
                //Si pasa a APPROVED creo las cuotas
                //Si pasa a REJECTED tiene cancelarce el cardToken.
                //Despues de los 5 intentos.
            }
        }
    }
    //Se encarga de tomar las actualizaciones de 'refreshPending()' y les realiza el primer pago.
    public function payFirstInstallments(){

        //Busca las sessions que NO tengan registros de subscriptions asociados
        $sessions = PlaceToPayTransaction::whereNotIn('id', function ($query) { $query->select('transactionId')->from('placetopay_subscriptions'); } )->get();
        foreach($sessions as $sessionToFirstPay){
            if($sessionToFirstPay->status === 'APPROVED'){
                // Pagar primer cuota.
                // Guardar registro con estado.
                // Si es rejected cancelar tarjeta
                // Si es aprroved crear demas cuotas
                // $sessionToFirstPay->token_collect_para_el_pago;
            }
        }
    }
    //Cobros que se realizan a tiempo, sin interrupciones de pago.
    public function stageOne(){

        // $d = Carbon\Carbon::create(2023, 9, 22);$date = $d->copy()->addMonth();

        $today = Carbon::now()->startOfDay();
        $date = $today->copy()->addMonth();

        //Tomar las Cuotas de hoy, segun un criterio:
        $subscriptionsToPay = PlaceToPaySubscription::whereDate('date_to_pay', '=', $date)
            ->where(['status'=> null])
            ->where(['nro_quote', '>=', 2])
            ->get();

        foreach($subscriptionsToPay as $subscriptionToPay){
            $subsSession = $subscriptionToPay->transaction->subscriptions;

            $pay = false;//Bandera para saber si tengo que pagar.
            foreach($subsSession as $subsc){
                //Explicacion: Si no tiene las cuotas anteriores APPROVED NO SE PAGA

                if($subsc->date_to_pay < $date){//Es una cuota anterior a mi fecha de cobro ?
                    if($subsc->status === 'APPROVED'){
                        $pay = true;
                        continue;
                    }else{
                        $pay = false;
                        break;
                    }
                }else{//es poterior a mi fecha de cobro
                    break;
                }
            }

            if($pay){
                //Pagar
                $result = $this->payIndividualPayment($subsc);
            }
        }
    }
    // END // Cronologia de cobro
}
