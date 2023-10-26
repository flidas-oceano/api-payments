<?php

namespace App\Services\PlaceToPay;

use App\Clients\ZohoClient;
use App\Models\Contact;
use App\Models\Contract;
use DateTime;
use stdClass;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\PlaceToPayTransaction;
use App\Models\PlaceToPaySubscription;
use App\Services\Zoho\ZohoService;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;

class PlaceToPayService
{
    private $login_pu;
    private $secret_pu;
    private $login_su;
    private $secret_su;
    private $zohoClient;


    public function __construct(ZohoClient $client)
    {
        $this->login_pu = env("REACT_APP_PTP_LOGIN_PU");
        $this->secret_pu = env("REACT_APP_PTP_SECRECT_PU");
        $this->login_su = env("REACT_APP_PTP_LOGIN_SU");
        $this->secret_su = env("REACT_APP_PTP_SECRECT_SU");
        $this->zohoClient = $client;
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

    public function paymentIsPending($response,$data, $sessionId, $quoteToPay){
        $pendingPayment = PlaceToPaySubscription::updateOrCreate(['requestId' => $response['requestId']], [
            'transactionId' => $sessionId,
            'nro_quote' => $quoteToPay,
            'date' => $response['status']['date'],
            'requestId' => $response['requestId'],
            'total' => $response['request']['payment']['amount']['total'],
            'currency' => $response['request']['payment']['amount']['currency'],
            'status' => $response['status']['status'],
            'date_to_pay' => $response['status']['date'],
            'reason' => $response['status']['reason'],
            'message' => $response['status']['message'],
            'authorization' => $response['payment'][0]['authorization'] ?? null,
            'reference' => $response['request']['payment']['reference'] ?? null,
        ]);

        return [
            "pendingPayment" => $pendingPayment,
            "response" => $response,
            "data" => $data,
        ];
    }

    public function pagarCuotaSuscripcion($request, $nro_quote, $renewSub)
    {
        $requestSubscriptionById = $this->getByRequestId($request['requestId'], false, true);
        $transaction = PlaceToPayTransaction::find($request->id);

        $payer = PlaceToPaySubscription::generatePayerPayment($requestSubscriptionById);

        $quoteToPay = $renewSub ? $transaction->subscriptions->first()->nro_quote : $nro_quote;

        $reference = $this->getNameReferenceSubscription($quoteToPay, $requestSubscriptionById['requestId'], $request['reference']);

        $subscriptionAmountToPay = new stdClass();
        $subscriptionAmountToPay->currency = $request->currency;

        if ($renewSub) {
            $subscriptionAmountToPay->total = $request->remaining_installments;
        } else {
            $subscriptionAmountToPay->total = $transaction->first_installment ?? $request->remaining_installments;
        }

        $payment = PlaceToPaySubscription::generatePayment($reference, $subscriptionAmountToPay);
        $auth = $this->generateAuthentication(true);
        $expiration = $this->getDateExpiration();
        $token = $request->token_collect_para_el_pago;

        // Data enviada a PlaceToPay para cobrar
        $data = PlaceToPaySubscription::generateDataPayment($auth, $payer, $payment, $token, $expiration);
        $response = $this->billSubscription($data, false);

        if (($response['payment'][0]['status']['status'] ?? null) === 'APPROVED') {
            // Actualizo el transactions, campo: installments_paid
            PlaceToPayTransaction::incrementInstallmentsPaid($transaction->id);

            if ($transaction->paymentLinks()->first() !== null) {
                $transaction->paymentLinks()->first()->update(['status' => 'Aprobado']);
            }
        }

        if ($response['status']['status'] === 'PENDING') {
            // Si el pago es pending, va a guardar y esperar a que sea APROBADO o RECHAZADO el pago para continuar con el flow
            $paymentPending = $this->paymentIsPending($response, $data, $transaction->id, $quoteToPay);
            return $paymentPending;
        }

        $request->nro_quote = $quoteToPay;

        if ($renewSub) {
            $newPayment = PlaceToPaySubscription::updateWith($request, $response, $transaction->subscriptions->first()->id);
        } else {
            $newPayment = PlaceToPaySubscription::createWith($request, $response);
        }

        // guardas registro primer cuota

        return [
            "newPayment" => $newPayment,
            "response" => $response,
            "data" => $data,
        ];
    }

    public function payFirstQuote($requestIdOfSubscription, $renewSuscription)
    {
        $transaction = PlaceToPayTransaction::where(['requestId' => $requestIdOfSubscription])->first();
        $firstQuote = $transaction->subscriptions->first();

        if ($firstQuote !== null) {
            $subscriptionByRequestId = $this->getByRequestId($firstQuote->requestId, false, true);

            /** @var PlaceToPaySubscription $firstQuote */
            $isPendingQuote = $firstQuote->isPending($transaction, $subscriptionByRequestId);

            /*
              return $this->isApprovedPayment($transaction,$subscriptionByRequestId);
             *
             * */

            if (is_array($isPendingQuote)) {
                $result = $this->pagarCuotaSuscripcion($transaction, $firstQuote->nro_quote, $renewSuscription);
                return $result;
            }

            if ($isPendingQuote === 'APPROVED') {
                return ['message' => 'El pago ya estaba aprobado', 'status' => $isPendingQuote, 'created_quotes' => count($transaction->subscriptions)];
            }
        }

        $result = $this->pagarCuotaSuscripcion($transaction, 1, $renewSuscription);
        return $result;
    }

    public function createRemainingInstallments($paymentDate, $requestsSubscription)
    {

        if (isset($requestsSubscription->subscriptions) && count($requestsSubscription->subscriptions) > 1) {
            return ['message' => 'Ya tiene cuotas'];
        }

        // crear cuotas
        if ($requestsSubscription->quotes > 1) {
            $dateParsedPaidFirstInstallment = date_parse($paymentDate);

            //Obtener
            $datesToPay = $this->getDatesToPay($dateParsedPaidFirstInstallment, $requestsSubscription->quotes);

            for ($i = 2; $i <= $requestsSubscription->quotes; $i++) {
                $dateToPay = $this->dateToPay($datesToPay[$i - 2]['year'], $datesToPay[$i - 2]['month'], $dateParsedPaidFirstInstallment['day']);

                PlaceToPaySubscription::create([
                    'transactionId' => $requestsSubscription->id,
                    'nro_quote' => $i,
                    'total' => $requestsSubscription->remaining_installments,
                    'currency' => $requestsSubscription->currency,
                    'date_to_pay' => date_format($dateToPay, 'Y-m-d H:i:s'),
                ]);
            }
        }
    }

    public function updateStatusRenewSessionSubscription($reference)
    {
        $sessionRenew = PlaceToPayTransaction::where('reference', $reference)
            ->where('status', 'RENEW')
            ->first();

        if ($sessionRenew !== null) {
            $sessionByRequestId = $this->getByRequestId($sessionRenew->requestId, $cron = false, $isSubscription = true);
            if (isset($sessionByRequestId['status']['status'])) {
                $placeToPayTransaction = PlaceToPayTransaction::where(['requestId' => $sessionByRequestId['requestId']])
                    ->update([
                        'status' => $sessionByRequestId['status']['status'],
                        'reason' => $sessionByRequestId['status']['reason'],
                        'message' => $sessionByRequestId['status']['message'],
                        'date' => $sessionByRequestId['status']['date'],
                    ]);
            }
        }
    }
    public function updateStatusSessionSubscription($SO)
    {
        $lastRequestSessionDB = PlaceToPayTransaction::where('reference', 'LIKE', '%' . $SO . '%')->orderBy('created_at', 'desc')->first();

        if ($lastRequestSessionDB !== null) {
            $sessionByRequestId = $this->getByRequestId($lastRequestSessionDB->requestId, $cron = false, $isSubscription = true);

            if (isset($sessionByRequestId['status']['status'])) {
                PlaceToPayTransaction::where(['requestId' => $sessionByRequestId['requestId']])->update([
                    'status' => $sessionByRequestId['status']['status'],
                    'reason' => $sessionByRequestId['status']['reason'],
                    'message' => $sessionByRequestId['status']['message'],
                    'date' => $sessionByRequestId['status']['date'],
                ]);
            }
        }
    }
    //Utils
    public function validateSignature($request, $type)
    {
        Log::debug('validateSignature-> Valor de $session:', [$request]);

        if ($type === 'payment') {
            $secretKey = $this->secret_pu;
        }else{
            $secretKey = $this->secret_su;
        }

        //Encriptamos
        $generatedSignature = sha1(
            $request['requestId'] .
            $request['status']['status'] .
            $request['status']['date'] .
            $secretKey
        );

        if ($generatedSignature === $request['signature']) {
            return true;
        } else {
            return false;
        }
    }

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
        if ($isSubscription) {
            $login = $this->login_su;
            $secretKey = $this->secret_su;
        } else {
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
    public function isResponseValid($response, $data = null, $cron = false)
    {
        // Verificar si la respuesta indica un fallo
        if (isset($response['status']['status']) && $response['status']['status'] === 'FAILED') {
            $errorReason = $response['status']['reason'];
            $errorMessage = $response['status']['message'];
            $errorDate = $response['status']['date'];

            if ($cron && $data != null) { //Esto esta porque la regla diaria de los pagos necesita que no rompa, pero si logear que hubo un error en el intento de pago
                $dataAsString = json_encode($data);
                // Log::channel('placetopay')->info("Payment request failed: Reason: $errorReason, Message: $errorMessage, Date: $errorDate, Data: $dataAsString");
                Log::channel('slack')->error("Ha fallado el request a PTP: ", ['response' => $response, 'data' => $data]);
            }
            if (!$cron) {
                throw new Exception("Payment request failed: Reason: $errorReason, Message: $errorMessage, Date: $errorDate");
            }
        }
    }
    public function getDatesToPay($dateParsedPaidFirstInstallment, $quotes)
    {
        $datesToPay = [];
        $datesToPay[] = $dateParsedPaidFirstInstallment;

        for ($i = 2; $i <= $quotes; $i++) {
            $datesToPay[] = date_parse(
                $this->dateToPay(
                    $datesToPay[$i - 2]['year'],
                    $datesToPay[$i - 2]['month'],
                    $dateParsedPaidFirstInstallment['day']
                )
            );
        }
        return $datesToPay;
    }
    public function dateToPay($año, $mes, $diaCobroPrimerCuota)
    {

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
        if ($diaCobroPrimerCuota > 28 && $mesSiguiente->ultimoDiaDelMes < $diaCobroPrimerCuota) {
            $mesSiguiente->fechaCobro = $mesSiguiente->primerDia->setDate(
                $mesSiguiente->primerDia->format('Y'),
                $mesSiguiente->primerDia->format('m'),
                $mesSiguiente->ultimoDiaDelMes
            );
        }
        if ($diaCobroPrimerCuota > 28 && $mesSiguiente->ultimoDiaDelMes >= $diaCobroPrimerCuota) {
            $mesSiguiente->fechaCobro = $mesSiguiente->primerDia->setDate(
                $mesSiguiente->primerDia->format('Y'),
                $mesSiguiente->primerDia->format('m'),
                $diaCobroPrimerCuota
            );
        }

        return $mesSiguiente->fechaCobro;
    }


    public function getNameReferenceSubscription($nroQuote, $requestIdSession, $contractId)
    {
        // $requestsSession = PlaceToPayTransaction::where(['requestId' => 680002])->get()->first();
        $requestsSession = PlaceToPayTransaction::where(['requestId' => $requestIdSession])->first();

        // $sessionsRejected = $requestsSession->subscriptions()->where(['status' => 'REJECTED', 'nro_quote' => 1])->get();
        $sessionsRejected = $requestsSession->subscriptions()->where(['status' => 'REJECTED', 'nro_quote' => $nroQuote])->get();

        if (count($sessionsRejected) === 0) {
            return $nroQuote . '_' . $contractId;
        }

        if (count($sessionsRejected) > 0) {
            return $nroQuote . '_' . $contractId . '_R_' . count($sessionsRejected);
        }
    }

    public function getNameReferenceSession($contractId)
    {

        // $requestsSessionByContractId = PlaceToPayTransaction::where('reference', 'LIKE', '%' . '2000339000617515006' . '%')->get();
        $requestsSessionByContractId = PlaceToPayTransaction::where('reference', 'LIKE', $contractId . '%')->get();

        if (count($requestsSessionByContractId) === 0) {
            return $contractId;
        }
        if (count($requestsSessionByContractId) > 0) {
            return $contractId . '_RT_' . count($requestsSessionByContractId);
        }
    }

    public function isRejectedTokenTransaction($requestsTransaction)
    {
        $cardToken = $requestsTransaction->token_collect_para_el_pago;
        $textoBuscado = 'CARD_REJECTED_';
        return str_contains($cardToken, $textoBuscado);
    }

    public function canCreateSession($SO)
    { //Se puede crear una nueva session ?

        // $placeToPayService->getNameReferenceSubscription(1,680007,'2000339000617515006');
        // $requestsSessionDB = PlaceToPayTransaction::where('reference', 'LIKE', '%' . '2000339000617515005' . '%')->get();
        // $requestsSessionDB = PlaceToPayTransaction::where('reference', 'LIKE', '%' . $SO . '%')->get();

        // $lastRequestSessionDB = PlaceToPayTransaction::where('reference', 'LIKE', '%' . $SO . '%')->orderBy('created_at', 'desc')->first();

        // $lastRequestSessionDB = PlaceToPayTransaction::where('reference', 'LIKE', '%' . '2000339000617515006' . '%')->orderBy('created_at', 'desc')->first();
        $lastRequestSessionDB = PlaceToPayTransaction::where('reference', 'LIKE', '%' . $SO . '%')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($lastRequestSessionDB !== null) {
            //Tengo registros con ese SO

            //VER SI TIENE EN PENDING LA SESSION ANTERIOR.
            if ($lastRequestSessionDB->status === 'PENDING') {
                return [
                    'canCreateSession' => false,
                ];
            }

            //VER SI TIENE EN PENDIENTE EL PAGO LA SESSION ANTERIOR.
            if ($lastRequestSessionDB->status === 'APPROVED') {
                $subscription = $lastRequestSessionDB->subscriptions()->where(['nro_quote' => 1])->orderBy('created_at', 'desc')->get()->first();
                if ($subscription->status == 'PENDING') {
                    return [
                        'canCreateSession' => false,
                    ];
                }

                //SI LA PRIMER CUOTA ESTA PAGADA EXITOSAMENTE NO CREAR UNA NUEVA SESSION.
                if ($subscription->status == 'APPROVED') {
                    return [
                        'canCreateSession' => false,
                    ];
                }
            }

            //VER SI TIENE EL CARD TOKEN REJECTED.
            if ($lastRequestSessionDB->status === 'APPROVED') {
                $subscription = $lastRequestSessionDB->subscriptions()->where(['nro_quote' => 1])->orderBy('created_at', 'desc')->get()->first();
                if ($subscription->status == 'REJECTED') {
                    if (!$this->isRejectedTokenTransaction($lastRequestSessionDB)) {
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
    public function revokeTokenSession($requestIdSession)
    {

        $requestSession = PlaceToPayTransaction::where(['requestId' => $requestIdSession])->first();

        $data = [
            "auth" => $this->generateAuthentication(),
            "locale" => "es_CO",
            "instrument" => [
                "token" => [
                    "token" => $requestSession->token_collect_para_el_pago
                ]
            ]
        ];
        $url = env("PTP_INSTRUMENT_INVALIDATE");
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($url, $data)->json();

        $this->isResponseValid($response);

        return $response;

    }
    public function create($data)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post(env('PTP_ENDPOINT'), $data)->json();

        $this->isResponseValid($response);

        return $response;
    }

    public function getByRequestId($requestId, $cron = null, $isSubscription = false)
    {
        if ($requestId === null) {
            throw new \InvalidArgumentException("El parámetro 'requestId' es obligatorio.");
        }

        $url = env('PTP_ENDPOINT') . "/" . $requestId;
        $data = [
            "auth" => $this->generateAuthentication($isSubscription),
        ];

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($url, $data)->json();

        $this->isResponseValid($response, $requestId, $cron);

        return $response;
    }



    public function billSubscription($data, $cron = null)
    {
        if ($data === null) {
            throw new \InvalidArgumentException("El parámetro 'data' es obligatorio.");
        }

        $url = env("PTP_COLLECT");
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($url, $data)->json();

        $this->isResponseValid($response, $data, $cron);

        return $response;
    }
    ///End URLS

    // Cronologia de cobro
    public function createInstallmentsWithoutPay($session)
    {
        //ver si ya se crearon las cuotas
        if (!(count($session->subscriptions) === $session->quotes)) {
            //No estan creadas todas las cuotas de la suscripcion

            for ($i = 2; $i <= $session->quotes; $i++) {
                PlaceToPaySubscription::create([
                    'transactionId' => $session->id,
                    'nro_quote' => $i,
                    'total' => $session->remaining_installments,
                    'currency' => $session->currency,
                ]);
            }
        }
    }

    public function payIndividualPayment($subscriptionToPay)
    {
        $session = PlaceToPayTransaction::find($subscriptionToPay->transaction->id);
        $paymentData = json_decode($session->paymentData);
        $reference = $this->getNameReferenceSubscription($subscriptionToPay->nro_quote, $session->requestId, $session->reference);
        $payment = PlaceToPaySubscription::generatePayment($reference, $subscriptionToPay);

        $auth = $this->generateAuthentication();
        $expiration = $this->generateAuthentication();
        $data = PlaceToPaySubscription::generateDataPayment($auth, $paymentData, $payment, $session->token_collect_para_el_pago, $expiration);

        $responsePayment = $this->billSubscription($data, $cron = true);

        //$responsePaymentStatus = $responsePayment['payment'][0]['status']['status'];
        $responseZohoUpdate = false;
        $responsePaymentStatus = 'REJECTED';



        if ($responsePaymentStatus === 'APPROVED') {
            // Actualizo el transactions, campo: installments_paid
            PlaceToPayTransaction::incrementInstallmentsPaid($session->id);
            $subscriptionToPay->update(['reference' => $responsePayment]);
            //Actualizo zoho

            $zohoService = new ZohoService($this->zohoClient);
            $responseZohoUpdate = $zohoService->updateTablePaymentsDetails($session->contract_id, $session, $subscriptionToPay);
        }

        if ($responsePaymentStatus === 'REJECTED') {
            // Actualizo la suscripcion, campo: failed_payment_attempts
            $howFailedAttempts = PlaceToPaySubscription::incrementFailedPaymentAttempts($subscriptionToPay->id);

            if (!($howFailedAttempts > 2)) {
                $updatedSubscription = PlaceToPaySubscription::duplicateAndReject($subscriptionToPay->id, $responsePayment, $payment);
                return [
                    "updateSubscription" => $updatedSubscription,
                    "responsePayment" => $responsePayment,
                    "bodyForPTP" => $data,
                ];
            } else {
                PlaceToPayTransaction::suspend($session);
            }

        }

        $updatedSubscription = PlaceToPaySubscription::updateSubscription($subscriptionToPay->id, $responsePayment, $payment);

        return [
            "zohoUpdate" => $responseZohoUpdate,
            "updateSubscription" => $updatedSubscription,
            "responsePayment" => $responsePayment,
            "bodyForPTP" => $data
        ];
    }

    //Refresca las sessions y las subscriptions de estado PENDING.
    public function refreshPendings()
    {
        $sessions = PlaceToPayTransaction::whereIn('status', ['OK', 'PENDING'])->get();

        $resultCommand = [
            'sessions' => [],
            'payments' => [],
        ];

        foreach ($sessions as $session) {
            $isSubscription = strpos($session->type, 'requestSubscription') !== false;
            $sessionFromPTP = $this->getByRequestId($session->requestId, true, $isSubscription);
            $resultCommand['sessions'][] = $sessionFromPTP;
            $statusSessionPTP = $sessionFromPTP['status']['status'] ?? false;

            //Actualizar session
            if ($statusSessionPTP) {

                $session->update([
                    'status' => $sessionFromPTP['status']['status'],
                    'reason' => $sessionFromPTP['status']['reason'],
                    'message' => $sessionFromPTP['status']['message'],
                    'date' => $sessionFromPTP['status']['date'],
                ]);

                if ($statusSessionPTP === 'FAILED') {
                    continue;
                }
                if ($session->isOneTimePayment()) {
                    continue;
                }

                // Guardar el cardToken
                if ($statusSessionPTP === "APPROVED") {
                    $session->approvedTokenCollect($sessionFromPTP['subscription']);

                    //Loque sigue lo maneja otra regla:
                    //Realizar el primer pago.
                    //Creacion de cuotas.
                    $this->createInstallmentsWithoutPay($session);

                }

                //Si pasa a REJECTED cancelar cardToken
                if ($statusSessionPTP === "REJECTED") {
                    $session->rejectTokenCollect($sessionFromPTP['subscription']);
                }

            }
        }

        $subscriptions = PlaceToPaySubscription::whereIn('status', ['OK', 'PENDING'])->get();
        foreach ($subscriptions as $subscription) {
            $session = $subscription->transaction;

            //$isSubscription = strpos($session->type, 'requestSubscription') !== false;

            $subscriptionFromPTP = $this->getByRequestId($subscription->requestId, true, true);
            $resultCommand['payments'][] = ["ptp" => $subscriptionFromPTP, "bd" => $subscription->toArray()];

            $statusPaymentPTP = $subscriptionFromPTP['status']['status'] ?? false;

            //Actualizar session
            if ($statusPaymentPTP) {

                $subscription->update([
                    'status' => $statusPaymentPTP,
                    'reason' => $subscriptionFromPTP['status']['reason'],
                    'message' => $subscriptionFromPTP['status']['message'],
                    'date' => $subscriptionFromPTP['status']['date'],
                ]);


                // Guardar el cardToken
                if ($statusPaymentPTP === "APPROVED") {
                    $zohoService = new ZohoService($this->zohoClient);

                    $contractDataToZoho = (object) [
                        "is_suscri" => $session->isSubscription(),
                        "is_advanceSuscription" => boolval($session->first_installment),
                        "requestId" => $session->requestId
                    ];

                    $dataToContract = Contract::mappingDataContract($contractDataToZoho,'Placetopay');
                    $dataToContact = Contact::mappingDataContact($contractDataToZoho->requestId,'Placetopay');

                    $contractUpdated = $zohoService->updateRecord('Sale_Orders',$dataToContract,$session->contract_id);
                    $contactUpdated = $zohoService->updateRecord('Contracts',$dataToContact,$session->contract_id);
                    $responseZohoUpdate = $zohoService->updateTablePaymentsDetails($session->contract_id, $session, $subscription);


                    $this->createInstallmentsWithoutPay($subscription->transaction);
                }

                //Si pasa a REJECTED cancelar cardToken
                if ($statusPaymentPTP === "REJECTED") {
                    //$session->rejectTokenCollect($sessionFromPTP['subscription']);

                    $howFailedAttempts = PlaceToPaySubscription::incrementFailedPaymentAttempts($subscription->id);

                    if (!($howFailedAttempts > 2)) {
                        $payment = $subscriptionFromPTP['request']['payment'];
                        $updatedSubscription = PlaceToPaySubscription::duplicateAndReject($subscription->id, $subscriptionFromPTP, $payment);
                    } else {
                        PlaceToPayTransaction::suspend($session);
                    }
                }

            }
        }

        return $resultCommand;
    }

    //Cobros que se realizan a tiempo, sin interrupciones de pago.
    public function stageOne()
    {
        //Tomar todas las Cuotas de status = NULL partiendo de la nro 2:
        $today = Carbon::now();
        $subscriptionsToPay = PlaceToPaySubscription::whereDate('date_to_pay', '<=', $today)->where('status', null)->where('nro_quote', '>=', 2)->get();
        $responseCommand = [
            "sessions" => [],
            "transactions" => [],
            "responsePayIndividual" => []
        ];

        foreach ($subscriptionsToPay as $subscriptionToPay) {
            $responseCommand['sessions'][] = $subscriptionToPay->transaction->toArray();
            $subsSession = $subscriptionToPay->transaction->subscriptions;
            $canPay = true;

            foreach ($subsSession as $subsc) {
                $dateToPay = Carbon::parse($subsc->date_to_pay);

                // Verificar si la cuota es anterior a la fecha de cobro
                if ($dateToPay->lt(now())) { // now = 10/10/2023 datetopay =
                    if ($subsc->status === null) {
                        $responseCommand['transactions'][] = $subsc->toArray();
                        break;
                    } else {
                        // No se puede pagar si es el día de la fecha y la cuota no está aprobada
                        $canPay = $subsc->status === 'APPROVED';
                        continue;
                    }
                }
            }

            if ($canPay) {
                // Pagar
                $responseCommand['responsePayIndividual'][] = $this->payIndividualPayment($subscriptionToPay);
            }
        }

        return $responseCommand;
    }
    // END // Cronologia de cobro

    public function extractSOFromReference($reference)
    {
        $segmentos = explode('_', $reference); // Divide la cadena por el carácter "_"

        $numeroMasLargo = 0;

        foreach ($segmentos as $segmento) {
            if (is_numeric($segmento) && strlen($segmento) > strlen($numeroMasLargo)) {
                $numeroMasLargo = $segmento;
            }
        }

        return $numeroMasLargo;
    }

    public function isOneTimePaymentOrQuoteOrSession($request)
    {
        $reference = $request->reference;
        // $entrada = "1_{entity_id_crm}_RT_6";
        $partes = explode('_', $reference);
        // Verifica si el primer elemento es un número y si es menor que 24
        if (is_numeric($partes[0]) && (int)$partes[0] <= 24) {
            //PlaceToPaySubscription::
            return 'quote'; //subscription
        } else {
            $session = PlaceToPayTransaction::where('requestId', $request->requestId)->first();
            return $session->type;
        }
    }

    public function updateZoho($session, $subscriptionToPay = null){

        $zohoService = new ZohoService($this->zohoClient);
        $responseZohoUpdate = $zohoService->updateTablePaymentsDetails($session->contract_id, $session, $subscriptionToPay);
    }
}
