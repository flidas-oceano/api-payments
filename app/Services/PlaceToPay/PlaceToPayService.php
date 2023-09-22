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
        // Sumar 24 horas a la fecha actual
        $currentDateTime->add(new \DateInterval('PT24H'));
        // Formatear la fecha para que coincida con el formato ISO 8601
        $seed = $currentDateTime->format('Y-m-d\TH:i:sP');

        return $seed;
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
        $requestSubscriptionById = $this->getByRequestId($request['requestId']);

        $transaccion = PlaceToPayTransaction::find($request->id);
        // $transaccion = PlaceToPayTransaction::find(41);

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
            $subscriptionByRequestId = $this->getByRequestId($subscription->requestId);
            if ( ($subscriptionByRequestId['payment'][0]['status']['status']??null) === 'APPROVED' ) {

                    // $result = $this->pagarCuotaSuscripcion($requestsSubscription, 1);
                    $requestSubscriptionById = $this->getByRequestId($requestsSubscription['requestId']);

                    if(($subscriptionByRequestId['payment'][0]['status']['status'] ?? null) === 'APPROVED'){
                        // Actualizo el transactions, campo: installments_paid
                        PlaceToPayTransaction::find($requestsSubscription->id)->update(['installments_paid' => DB::raw('COALESCE(installments_paid, 0) + 1')]);


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
            $sessionByRequestId =  $this->getByRequestId($lastRequestSessionDB->requestId);
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
    public function generateAuthentication()
    {
        $login = $this->login_pu;
        $secretKey = $this->secret_pu;
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
    public function getByRequestId($requestId)
    {
        if ($requestId === null) {
            throw new \InvalidArgumentException("El parámetro 'requestId' es obligatorio.");
        }

        $url = "https://checkout-test.placetopay.ec/api/session/" . $requestId;
        $data = [
            "auth" => $this->generateAuthentication(),
        ];

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($url, $data)->json();

        $this->isResponseValid($response);

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
                            // 'transactionId' => $request->id,
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
    // END // Cronologia de cobro

    // //Objetos de ejemplo
    public function pasounocreatesession()
    {
        $createRequest = json_decode('{
                "buyer": {
                    "name": "Demetris",
                    "surname": "Quigley",
                    "email": "dnetix@yopmail.com",
                    "document": "1040035000",
                    "documentType": "CC",
                    "mobile": 3006108300
                },
                "payment": {
                    "reference": "TEST_20230807_174144",
                    "description": "Cupiditate amet saepe fuga optio et.",
                    "amount": {
                    "currency": "COP",
                    "total": 167000
                    }
                },
                "expiration": "2023-08-08T17:41:44-05:00",
                "ipAddress": "190.49.106.4",
                "returnUrl": "https://dnetix.co/p2p/client",
                "userAgent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36",
                "paymentMethod": null,
                "auth": {
                    "login": "fcba839f74386227ed54fea97404b099",
                    "tranKey": "xYju9N9XFfLOpkAyUgfKnzIN9CM=",
                    "nonce": "TjJRNE9HSmpaVGt4TnpoalpUZzVOVGxoWlROa1lqQXpOMlE0WkRZMk1HRT0=",
                    "seed": "2023-08-07T17:41:48-05:00"
                }
            }');

        $responseCreateRequest = json_decode('{
            "status": {
                "status": "OK",
                "reason": "PC",
                "message": "La petición se ha procesado correctamente",
                "date": "2023-08-07T17:41:48-05:00"
            },
            "requestId": "669292",
            "processUrl": "https://checkout-test.placetopay.ec/spa/session/669292/36f326c478555ec0dbe2fb4dc9683f81"
        }');

        //crear una suscripcion es:
        //Guardar las transacciones ok. Que todavia estan sin pagar.
        $placeToPayTransactions = new stdClass();
        $placeToPayTransactions->requestId = $responseCreateRequest->requestId;
        $placeToPayTransactions->status = $responseCreateRequest->status->status;
        $placeToPayTransactions->date = $responseCreateRequest->status->date; // solo se guarda cuando se crea la transaccion ok. y cuando cambia de estado definitivo approved, rejected,etc.
        $placeToPayTransactions->reason = $responseCreateRequest->status->reason;
        $placeToPayTransactions->message = $responseCreateRequest->status->message;
        $placeToPayTransactions->processUrl = $responseCreateRequest->processUrl;

        //requestId: 669292
        $getRequestInformation = json_decode('{
                "auth": {
                "login": "fcba839f74386227ed54fea97404b099",
                "tranKey": "vxBYop+GDsCBBzzAND5GGbhaeEc=",
                "nonce": "WldWak9HSmxNRFUyWkRobVlXUXdObUk0TkRNNFl6SmxaR0poWTJZMFptST0=",
                "seed": "2023-08-07T17:42:15-05:00"
            }
        }');
        //VISA 4716375184092180 12/29 111 PENDING
        $responsePendingGetRequestInformation = json_decode('{
                "requestId": "669292",
                "status": {
                    "status": "PENDING",
                    "reason": "PC",
                    "message": "La petición se encuentra activa",
                    "date": "2023-08-07T17:42:15-05:00"
                },
                "request": {
                    "locale": "es_CO",
                    "buyer": {
                    "document": "1040035000",
                    "documentType": "CC",
                    "name": "Demetris",
                    "surname": "Quigley",
                    "email": "dnetix@yopmail.com",
                    "mobile": "3006108300"
                    },
                    "payment": {
                    "reference": "TEST_20230807_174144",
                    "description": "Cupiditate amet saepe fuga optio et.",
                    "amount": {
                        "currency": "COP",
                        "total": 167000
                    },
                    "allowPartial": false,
                    "subscribe": false
                    },
                    "returnUrl": "https://dnetix.co/p2p/client",
                    "ipAddress": "190.49.106.4",
                    "userAgent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36",
                    "expiration": "2023-08-08T17:41:44-05:00",
                    "captureAddress": false,
                    "skipResult": false,
                    "noBuyerFill": false
                }
            }');
        //buscar por requestId
        $placeToPayTransactions->requestId = $responsePendingGetRequestInformation->requestId;
        $placeToPayTransactions->status = $responsePendingGetRequestInformation->status->status;
        $placeToPayTransactions->date = $responsePendingGetRequestInformation->status->date; //se guarda el estado porque tiene que ser pagado.
        $placeToPayTransactions->reason = $responsePendingGetRequestInformation->status->reason;
        $placeToPayTransactions->message = $responsePendingGetRequestInformation->status->message;
        $placeToPayTransactions->currency = $responsePendingGetRequestInformation->request->payment->amount->currency;
        $placeToPayTransactions->total = $responsePendingGetRequestInformation->request->payment->amount->total;
        $placeToPayTransactions->reference = $responsePendingGetRequestInformation->request->payment->reference; // enrealidad buscar el buyer en contactos.
        $placeToPayTransactions->requestId = $responsePendingGetRequestInformation->requestId;

        $createRequest2 = json_decode('{
                "buyer": {
                    "name": "Demetris",
                    "surname": "Quigley",
                    "email": "dnetix@yopmail.com",
                    "document": "1040035000",
                    "documentType": "CC",
                    "mobile": 3006108300
                },
                "payment": {
                    "reference": "TEST_20230807_174144",
                    "description": "Cupiditate amet saepe fuga optio et.",
                    "amount": {
                    "currency": "COP",
                    "total": 167000
                    }
                },
                "expiration": "2023-08-08T17:41:44-05:00",
                "ipAddress": "190.49.106.4",
                "returnUrl": "https://dnetix.co/p2p/client",
                "userAgent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36",
                "paymentMethod": null,
                "auth": {
                    "login": "fcba839f74386227ed54fea97404b099",
                    "tranKey": "WJRKDDrIwMUDK6DuRjx8dVefkiE=",
                    "nonce": "T1RCak5qQmtOalZqTmpOak5tUTFPR1l5Tm1GaE1EUTJNREk0TWpJd00yTT0=",
                    "seed": "2023-08-07T17:50:04-05:00"
                }
            }');
        $responseCreateRequest2 = json_decode('{
                "status": {
                    "status": "OK",
                    "reason": "PC",
                    "message": "La petición se ha procesado correctamente",
                    "date": "2023-08-07T17:50:04-05:00"
                },
                "requestId": "669295",
                "processUrl": "https://checkout-test.placetopay.ec/spa/session/669295/88bb48a38fa3e87b1aa9794daf7b392d"
            }');

        //requestId: 669295
        $getRequestInformation2 = json_decode('{
                "auth": {
                    "login": "fcba839f74386227ed54fea97404b099",
                    "tranKey": "xkBRio2rGuuXm8kZ1s7bdVv5qFs=",
                    "nonce": "TkRGbU56TXdZalJtTnpGbE1URTRNbVF4TVdOa05EazBaVFJpWWpRME5qYz0=",
                    "seed": "2023-08-07T17:55:25-05:00"
                }
            }');
        $responsePendingGetRequestInformation = json_decode('{
                "requestId": "669295",
                "status": {
                    "status": "PENDING",
                    "reason": "PC",
                    "message": "La petición se encuentra activa",
                    "date": "2023-08-07T17:55:25-05:00"
                },
                "request": {
                    "locale": "es_CO",
                    "buyer": {
                    "document": "1040035000",
                    "documentType": "CC",
                    "name": "Demetris",
                    "surname": "Quigley",
                    "email": "dnetix@yopmail.com",
                    "mobile": "3006108300"
                    },
                    "payment": {
                    "reference": "TEST_20230807_174144",
                    "description": "Cupiditate amet saepe fuga optio et.",
                    "amount": {
                        "currency": "COP",
                        "total": 167000
                    },
                    "allowPartial": false,
                    "subscribe": false
                    },
                    "returnUrl": "https://dnetix.co/p2p/client",
                    "ipAddress": "190.49.106.4",
                    "userAgent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36",
                    "expiration": "2023-08-08T17:41:44-05:00",
                    "captureAddress": false,
                    "skipResult": false,
                    "noBuyerFill": false
                }
            }');
        //VISA 4716375184092180 12/29 111 APROVED
        $responseAprovedgGetRequestInformation = '';
    }
    public function subscription()
    {
        // Payment Request (createRequest)
        $createRequest = '{
                "buyer": {
                    "name": "Virginie",
                    "surname": "Koch",
                    "email": "dnetix@yopmail.com",
                    "document": "1040035000",
                    "documentType": "CC",
                    "mobile": 3006108300
                },
                "subscription": {
                    "reference": "S_3119",
                    "description": "Suscripcion de prueba."
                },
                "expiration": "2023-08-08T18:01:42-05:00",
                "ipAddress": "190.49.106.4",
                "returnUrl": "https://dnetix.co/p2p/client",
                "userAgent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36",
                "paymentMethod": null,
                "auth": {
                    "login": "dc630cc168489ebed4cdd94c0c1a1dd9",
                    "tranKey": "Hlce5AbG2t6/UU1hhw5RJ8ILDGA=",
                    "nonce": "WXpObE0yWm1NMlJpTlRoalpEYzBORE5pTTJNM09HWTFNVGt6TmpJMFptST0=",
                    "seed": "2023-08-07T18:01:47-05:00"
                }
            }';
        $responseCreateRequest = '{
                "status": {
                    "status": "OK",
                    "reason": "PC",
                    "message": "La petición se ha procesado correctamente",
                    "date": "2023-08-07T18:01:47-05:00"
                },
                "requestId": "669300",
                "processUrl": "https://checkout-test.placetopay.ec/spa/session/669300/b2bfc90a0d2c4e9528472925e79f13f7"
            }';

        //Request Information (getRequestInformation)
        //requestId: 669300 PENDING
        $getRequestInformationRequest = '{
                "auth": {
                    "login": "dc630cc168489ebed4cdd94c0c1a1dd9",
                    "tranKey": "2jaJTQIm6bi8ONAxpS8QLGEBf9k=",
                    "nonce": "T1RSaE5EZGpOakU1TXpkaVlqQTRPRGxoWVRVd1l6TTNOV1EyWldKaVkyVT0=",
                    "seed": "2023-08-07T18:02:41-05:00"
                }
            }';
        $responsePendingGetRequestInformationRequest = '{
                "requestId": "669300",
                "status": {
                    "status": "PENDING",
                    "reason": "PC",
                    "message": "La petición se encuentra activa",
                    "date": "2023-08-07T18:02:41-05:00"
                },
                "request": {
                    "locale": "es_CO",
                    "buyer": {
                    "document": "1040035000",
                    "documentType": "CC",
                    "name": "Virginie",
                    "surname": "Koch",
                    "email": "dnetix@yopmail.com",
                    "mobile": "3006108300"
                    },
                    "subscription": {
                    "reference": "S_3119",
                    "description": "Suscripcion de prueba."
                    },
                    "returnUrl": "https://dnetix.co/p2p/client",
                    "ipAddress": "190.49.106.4",
                    "userAgent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36",
                    "expiration": "2023-08-08T18:01:42-05:00",
                    "captureAddress": false,
                    "skipResult": false,
                    "noBuyerFill": false
                }
            }';
        //despues del (lighbox) pagada
        //VISA 4111111111111111 12/29 111 APPROVED
        $responseApprovedGetRequestInformation = '{
                "requestId": "669300",
                "status": {
                    "status": "APPROVED",
                    "reason": "00",
                    "message": "La petición ha sido aprobada exitosamente",
                    "date": "2023-08-07T18:05:20-05:00"
                },
                "request": {
                    "locale": "es_CO",
                    "payer": {
                    "document": "1040035000",
                    "documentType": "CC",
                    "name": "Virginie",
                    "surname": "Koch",
                    "email": "dnetix@yopmail.com",
                    "mobile": "+593958743282"
                    },
                    "buyer": {
                    "document": "1040035000",
                    "documentType": "CC",
                    "name": "Virginie",
                    "surname": "Koch",
                    "email": "dnetix@yopmail.com",
                    "mobile": "3006108300"
                    },
                    "subscription": {
                    "reference": "S_3119",
                    "description": "Suscripcion de prueba."
                    },
                    "returnUrl": "https://dnetix.co/p2p/client",
                    "ipAddress": "190.49.106.4",
                    "userAgent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36",
                    "expiration": "2023-08-08T18:01:42-05:00",
                    "captureAddress": false,
                    "skipResult": false,
                    "noBuyerFill": false
                },
                "subscription": {
                    "type": "token",
                    "status": {
                    "status": "OK",
                    "reason": "00",
                    "message": "La petición se ha procesado correctamente",
                    "date": "2023-08-07T18:04:55-05:00" //este dato es el date de cuando se puse la tarjeta en el lightbox
                    },
                    "instrument": [
                    {
                        "keyword": "token",
                        "value": "5c35d41ce18026788b43195ec6e2ae15baaa7ed0975caa041926400ff0af4bd8",
                        "displayOn": "none"
                    },
                    {
                        "keyword": "subtoken",
                        "value": "7652366690091111",
                        "displayOn": "none"
                    },
                    {
                        "keyword": "franchise",
                        "value": "visa",
                        "displayOn": "none"
                    },
                    {
                        "keyword": "franchiseName",
                        "value": "Visa",
                        "displayOn": "none"
                    },
                    {
                        "keyword": "issuerName",
                        "value": "JPMORGAN CHASE BANK, N.A.",
                        "displayOn": "none"
                    },
                    {
                        "keyword": "lastDigits",
                        "value": "1111",
                        "displayOn": "none"
                    },
                    {
                        "keyword": "validUntil",
                        "value": "2029-12-31",
                        "displayOn": "none"
                    },
                    {
                        "keyword": "installments",
                        "value": null,
                        "displayOn": "none"
                    }
                    ]
                }
            }';

        //Con el requesti id buscas el token y con el el collect realizas el pago.
        //cada request te devuelve un registro de pago.

        // Collect Request (collect) pago de suscripcion.
        $collectRequest = '{
                "payer": {
                    "name": "Corbin",
                    "surname": "Glover",
                    "email": "zcummerata@yahoo.com",
                    "document": "1040035020",
                    "documentType": "CC"
                },
                "payment": {
                    "reference": "TEST_20230807_180142",
                    "description": "Rerum assumenda et modi beatae quo rem.",
                    "amount": {
                        "currency": "USD",
                        "total": 1000
                    }
                },
                "instrument": {
                    "token": {
                        "token": "5c35d41ce18026788b43195ec6e2ae15baaa7ed0975caa041926400ff0af4bd8" //token del instrument de la suscripcino
                    }
                }
            }';
        $responseApprovedCollectRequest = '{
                "requestId": "669305",
                "status": {
                    "status": "APPROVED",
                    "reason": "00",
                    "message": "La petición ha sido aprobada exitosamente",
                    "date": "2023-08-07T18:15:44-05:00"
                },
                "request": {
                    "locale": "es_CO",
                    "payer": {
                        "document": "1040035020",
                        "documentType": "CC",
                        "name": "Corbin",
                        "surname": "Glover",
                        "email": "zcummerata@yahoo.com"
                    },
                    "payment": {
                        "reference": "TEST_20230807_180142",
                        "description": "Rerum assumenda et modi beatae quo rem.",
                        "amount": {
                            "currency": "USD",
                            "total": 1000
                        },
                        "allowPartial": false,
                        "subscribe": false
                    },
                    "returnUrl": "https://checkout-test.placetopay.ec/home",
                    "ipAddress": "2604:a880:400:d0::706:1001",
                    "userAgent": "GuzzleHttp/6.5.5 curl/7.47.0 PHP/7.4.9",
                    "expiration": "2023-08-07T18:45:43-05:00",
                    "captureAddress": false,
                    "skipResult": false,
                    "noBuyerFill": false
                },
                "payment": [
                    {
                    "status": {
                        "status": "APPROVED",
                        "reason": "00",
                        "message": "Aprobada",
                        "date": "2023-08-07T18:15:44-05:00"
                    },
                    "internalReference": "394740",
                    "paymentMethod": "visa",
                    "paymentMethodName": "Visa",
                    "issuerName": "JPMORGAN CHASE BANK, N.A.",
                    "amount": {
                        "from": {
                            "currency": "USD",
                            "total": 1000
                        },
                        "to": {
                            "currency": "USD",
                            "total": 1000
                        },
                        "factor": 1
                    },
                    "authorization": "999999",
                    "reference": "TEST_20230807_180142",
                    "receipt": "394740",
                    "franchise": "ID_VS",
                    "refunded": false,
                    "processorFields": [
                        {
                        "keyword": "merchantCode",
                        "value": "1465675",
                        "displayOn": "none"
                        },
                        {
                        "keyword": "terminalNumber",
                        "value": "00990099",
                        "displayOn": "none"
                        },
                        {
                        "keyword": "credit",
                        "value": {
                            "code": 1,
                            "type": "00",
                            "groupCode": "C",
                            "installments": 1
                        },
                        "displayOn": "none"
                        },
                        {
                        "keyword": "totalAmount",
                        "value": 1000,
                        "displayOn": "none"
                        },
                        {
                        "keyword": "interestAmount",
                        "value": 0,
                        "displayOn": "none"
                        },
                        {
                        "keyword": "installmentAmount",
                        "value": 1000,
                        "displayOn": "none"
                        },
                        {
                        "keyword": "iceAmount",
                        "value": 0,
                        "displayOn": "none"
                        },
                        {
                        "keyword": "bin",
                        "value": "411111",
                        "displayOn": "none"
                        },
                        {
                        "keyword": "expiration",
                        "value": "1229",
                        "displayOn": "none"
                        },
                        {
                        "keyword": "userAgent",
                        "value": "GuzzleHttp/6.5.5 curl/7.47.0 PHP/7.4.9",
                        "displayOn": "none"
                        },
                        {
                        "keyword": "lastDigits",
                        "value": "1111",
                        "displayOn": "none"
                        },
                        {
                        "keyword": "id",
                        "value": "2e197bb637571e807b8b5376b4f68e99",
                        "displayOn": "none"
                        },
                        {
                        "keyword": "b24",
                        "value": "00",
                        "displayOn": "none"
                        }
                    ]
                    }
                ]
            }';
        // Collect Request (collect) pago2 de suscripcion. Con el mismo token. Da requestId diferentes.
        $collectRequest2 = '{
                "payer": {
                    "name": "Corbin",
                    "surname": "Glover",
                    "email": "zcummerata@yahoo.com",
                    "document": "1040035020",
                    "documentType": "CC"
                },
                "payment": {
                    "reference": "TEST_20230807_180142",
                    "description": "Rerum assumenda et modi beatae quo rem.",
                    "amount": {
                    "currency": "USD",
                    "total": 1001
                    }
                },
                "instrument": {
                    "token": {
                    "token": "5c35d41ce18026788b43195ec6e2ae15baaa7ed0975caa041926400ff0af4bd8"
                    }
                }
            }';
        $responseApprovedCollectRequest2 = '{
                "requestId": "669306",
                "status": {
                    "status": "APPROVED",
                    "reason": "00",
                    "message": "La petición ha sido aprobada exitosamente",
                    "date": "2023-08-07T18:18:42-05:00"
                },
                "request": {
                    "locale": "es_CO",
                    "payer": {
                    "document": "1040035020",
                    "documentType": "CC",
                    "name": "Corbin",
                    "surname": "Glover",
                    "email": "zcummerata@yahoo.com"
                    },
                    "payment": {
                    "reference": "TEST_20230807_180142",
                    "description": "Rerum assumenda et modi beatae quo rem.",
                    "amount": {
                        "currency": "USD",
                        "total": 1001
                    },
                    "allowPartial": false,
                    "subscribe": false
                    },
                    "returnUrl": "https://checkout-test.placetopay.ec/home",
                    "ipAddress": "2604:a880:400:d0::706:1001",
                    "userAgent": "GuzzleHttp/6.5.5 curl/7.47.0 PHP/7.4.9",
                    "expiration": "2023-08-07T18:48:41-05:00",
                    "captureAddress": false,
                    "skipResult": false,
                    "noBuyerFill": false
                },
                "payment": [
                    {
                    "status": {
                        "status": "APPROVED",
                        "reason": "00",
                        "message": "Aprobada",
                        "date": "2023-08-07T18:18:42-05:00"
                    },
                    "internalReference": "394743",
                    "paymentMethod": "visa",
                    "paymentMethodName": "Visa",
                    "issuerName": "JPMORGAN CHASE BANK, N.A.",
                    "amount": {
                        "from": {
                        "currency": "USD",
                        "total": 1001
                        },
                        "to": {
                        "currency": "USD",
                        "total": 1001
                        },
                        "factor": 1
                    },
                    "authorization": "999999",
                    "reference": "TEST_20230807_180142",
                    "receipt": "394743",
                    "franchise": "ID_VS",
                    "refunded": false,
                    "processorFields": [
                        {
                        "keyword": "merchantCode",
                        "value": "1465675",
                        "displayOn": "none"
                        },
                        {
                        "keyword": "terminalNumber",
                        "value": "00990099",
                        "displayOn": "none"
                        },
                        {
                        "keyword": "credit",
                        "value": {
                            "code": 1,
                            "type": "00",
                            "groupCode": "C",
                            "installments": 1
                        },
                        "displayOn": "none"
                        },
                        {
                        "keyword": "totalAmount",
                        "value": 1001,
                        "displayOn": "none"
                        },
                        {
                        "keyword": "interestAmount",
                        "value": 0,
                        "displayOn": "none"
                        },
                        {
                        "keyword": "installmentAmount",
                        "value": 1001,
                        "displayOn": "none"
                        },
                        {
                        "keyword": "iceAmount",
                        "value": 0,
                        "displayOn": "none"
                        },
                        {
                        "keyword": "bin",
                        "value": "411111",
                        "displayOn": "none"
                        },
                        {
                        "keyword": "expiration",
                        "value": "1229",
                        "displayOn": "none"
                        },
                        {
                        "keyword": "userAgent",
                        "value": "GuzzleHttp/6.5.5 curl/7.47.0 PHP/7.4.9",
                        "displayOn": "none"
                        },
                        {
                        "keyword": "lastDigits",
                        "value": "1111",
                        "displayOn": "none"
                        },
                        {
                        "keyword": "id",
                        "value": "da0b396730f5316621dc23a5cc7e871c",
                        "displayOn": "none"
                        },
                        {
                        "keyword": "b24",
                        "value": "00",
                        "displayOn": "none"
                        }
                    ]
                    }
                ]
            }';
    }
    public function updateSession()
    {
        $requestQueEspero = '{
                "redirectUrl": null,
                "redirectsOutOfLightbox": false,
                "session": {
                    "id": 674259,
                    "code": "S674259-T4",
                    "locale": "es_CO",
                    "type": "P",
                    "skipResult": false,
                    "showBanner": false,
                    "status": {
                        "status": "APPROVED",
                        "resume": "El proceso de pago ha finalizado",
                        "message": null,
                        "updatedAt": "2023-08-24T21:37:47.000000Z"
                    },
                    "payment": {
                        "allowPartial": false,
                        "remaining": "0",
                        "minPaymentValue": "1",
                        "paid": "53226000",
                        "recurring": null,
                        "reference": "dasdasds",
                        "description": "Prueba contrato de OceanoMedicina",
                        "amount": {
                            "currency": "USD",
                            "total": "53226000",
                            "taxes": null,
                            "details": null
                        },
                        "items": null,
                        "subscribe": false
                    },
                    "subscription": null,
                    "returnUrl": "https://dnetix.co/p2p/client",
                    "date": "2023-08-24T21:35:50.000000Z",
                    "expirationMessage": "El proceso de pago se terminó",
                    "expiration": "2023-08-25T21:35:50.000000Z",
                    "fields": [
                        {
                            "label": "_processUrl_",
                            "value": "https://checkout-test.placetopay.ec/spa/session/674259/c77f992a37635813687be563535224d4",
                            "displayOn": "none"
                        }
                    ]
                },
                "payments": [
                    {
                        "status": "APPROVED",
                        "resume": "Transacción Aprobada",
                        "message": null,
                        "date": "2023-08-24T21:37:46.000000Z",
                        "authorization": "999999",
                        "lastDigits": "1111",
                        "accountNumber": null,
                        "amount": "53226000",
                        "paid": "53226000",
                        "discount": null,
                        "interest": null,
                        "receipt": "399767",
                        "ipAddress": "2800:810:471:194b:2908:25ca:58a:282e",
                        "reference": "dasdasds",
                        "paymentMethodLabel": "Visa",
                        "paymentMethod": "visa",
                        "responseCode": "00",
                        "account": null,
                        "payOrderPdfUrl": null,
                        "transactionType": null,
                        "redirectUrl": null
                    }
                ],
                "subscriptions": [],
                "notifyData": {
                    "status": {
                        "status": "APPROVED",
                        "reason": "00",
                        "message": "La petición ha sido aprobada exitosamente",
                        "date": "2023-08-24T16:37:47-05:00"
                    },
                    "requestId": 674259,
                    "reference": "dasdasds",
                    "signature": "35ed6b52b04df3a372f2a3a84a49cd397d5bf1de"
                },
                "captcha": null
            }';
    }
    public function GetPaymentByRequestId()
    {
        // (pagounico)
        $jsonPaymentByIdRequest = '{
                "auth":' . $this->generateAuthentication() . '
              }';
        //Get del payment(pagounico) recien creado sin pagar todavia.
        $jsonPaymentByIdResponse = '{
                "requestId": "668933",
                "status": {
                  "status": "PENDING",
                  "reason": "PC",
                  "message": "La petición se encuentra activa",
                  "date": "2023-08-05T09:59:16-05:00"
                },
                "request": {
                  "locale": "es_CO",
                  "buyer": {
                    "document": "1040035000",
                    "documentType": "CC",
                    "name": "Rahsaan",
                    "surname": "Auer",
                    "email": "dnetix@yopmail.com",
                    "mobile": "3006108300"
                  },
                  "payment": {
                    "reference": "TEST_20230805_095901",
                    "description": "Voluptatem qui et hic numquam magnam.",
                    "amount": {
                      "currency": "COP",
                      "total": 140000
                    },
                    "allowPartial": false,
                    "subscribe": false
                  },
                  "returnUrl": "https://dnetix.co/p2p/client",
                  "ipAddress": "190.49.106.4",
                  "userAgent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36",
                  "expiration": "2023-08-06T09:59:01-05:00",
                  "captureAddress": false,
                  "skipResult": false,
                  "noBuyerFill": false
                }
              }';

        $arrayRequest = json_decode($jsonPaymentByIdRequest, true);
        $arrayResponse = json_decode($jsonPaymentByIdResponse, true);

        return response()->json([
            $arrayRequest,
            $arrayResponse
        ]);
    }
    public function createPayment()
    {
        // (pagounico)
        $jsonCreatePaymentRequest = '{
                "buyer": {
                  "name": "Otha",
                  "surname": "Kautzer",
                  "email": "dnetix@yopmail.com",
                  "document": "1040035000",
                  "documentType": "CC",
                  "mobile": 3006108300
                },
                "payment": {
                  "reference": "TEST_20230804_153102",
                  "description": "Consequatur sit dicta rem ut a praesentium.",
                  "amount": {
                    "currency": "COP",
                    "total": 106000
                  }
                },
                "expiration": "2023-08-05T15:31:02-05:00",
                "ipAddress": "186.19.80.249",
                "returnUrl": "https://dnetix.co/p2p/client",
                "userAgent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36",
                "paymentMethod": null,
                "auth":' . $this->generateAuthentication() . '
              }';
        //   (pagounico)
        $jsonCreatePaymentResponse = '{
                  "status": {
                      "status": "OK",
                      "reason": "PC",
                      "message": "La petición se ha procesado correctamente",
                      "date": "2023-08-05T09:59:09-05:00"
                    },
                    "requestId": "668933",
                    "processUrl": "https://checkout-test.placetopay.ec/spa/session/668933/18a1c7856f0ea52b55df228f9115639b"
                }';

        $arrayRequest = json_decode($jsonCreatePaymentRequest, true);
        $arrayResponse = json_decode($jsonCreatePaymentResponse, true);

        return response()->json([
            $arrayRequest,
            $arrayResponse
        ]);
    }
    //End //Objetos de ejemplo
}
