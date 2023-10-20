<?php

namespace App\Services\PlaceToPay;

use App\Clients\ZohoClient;
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
    public function pagarCuotaSuscripcionAnticipo()
    {
    }
    public function payInstallmentsSubscriptions()
    {
        PlaceToPaySubscription::where('status', '!=', 'APPROVED')->get();
    }
    public function pagarCuotaSuscripcion($request, $nro_quote, $renewSub)
    {
        $requestSubscriptionById = $this->getByRequestId($request['requestId'], $cron = false, $isSubscription = true);

        $transaccion = PlaceToPayTransaction::find($request->id);

        // pagar primer cuota de subscripcion normal, no anticipo
        $payer = PlaceToPaySubscription::generatePayerPayment($requestSubscriptionById);

        $quoteToPay = $renewSub ? $transaccion->subscriptions->first()->nro_quote : $nro_quote;

        $reference = $this->getNameReferenceSubscription($quoteToPay, $requestSubscriptionById['requestId'], $request['reference']);

        $subscriptionToPay = new stdClass();
        $subscriptionToPay->currency = $request->currency;

        if ($renewSub) {
            $subscriptionToPay->total = $request->remaining_installments;
        } else {
            $subscriptionToPay->total = $transaccion->first_installment ?? $request->remaining_installments;
        }

        $payment = PlaceToPaySubscription::generatePayment($reference, $subscriptionToPay);

        $auth = $this->generateAuthentication($isSubscription = true);
        $expiration = $this->getDateExpiration();
        $token = $request->token_collect_para_el_pago;

        $data = PlaceToPaySubscription::generateDataPayment($auth, $payer, $payment, $token, $expiration);

        $response = $this->billSubscription($data, $cron = false);

        if (($response['payment'][0]['status']['status'] ?? null) === 'APPROVED') {
            // Actualizo el transactions, campo: installments_paid
            PlaceToPayTransaction::incrementInstallmentsPaid($transaccion->id);

            if ($transaccion->paymentLinks()->first() !== null) {
                $transaccion->paymentLinks()->first()->update(['status' => 'Aprobado']);
            }
        }

        $request->nro_quote = $quoteToPay;

        $newPayment = null;

        if ($renewSub) {
            $newPayment = PlaceToPaySubscription::updateWith($request, $response, $transaccion->subscriptions->first()->id);
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

            $isPendingQuote = $firstQuote->isPending($transaction);

            if (is_array($isPendingQuote)) {
                $result = $this->pagarCuotaSuscripcion($transaction, $firstQuote->nro_quote, $renewSuscription); //TODO: help
                return $result;
            }

            if ($isPendingQuote === 'APPROVED') {
                return ['message' => 'El pago ya estaba aprobado', 'status' => $isPendingQuote, 'created_quotes' => count($transaction->subscriptions)];
            }
        }

        $result = $this->pagarCuotaSuscripcion($transaction, 1, $renewSuscription);
        return $result;
    }

    public function payFirstQuoteCreateRestQuotesByRequestId($requestIdRequestSubscription) // TODO: ya no se usa
    {
        $requestsSubscription = PlaceToPayTransaction::where(['requestId' => $requestIdRequestSubscription])->first();

        $subscription = $requestsSubscription->subscriptions->first();

        if (($subscription->status ?? null) === 'PENDING') {
            //Actualizar la primer cuota que pasa de PENDING a APPROVED
            $subscriptionByRequestId = $this->getByRequestId($subscription->requestId, $cron = false, $isSubscription = true);
            if (($subscriptionByRequestId['payment'][0]['status']['status'] ?? null) === 'APPROVED') {

                $requestSubscriptionById = $this->getByRequestId($requestsSubscription['requestId'], $cron = false, $isSubscription = true);

                if (($subscriptionByRequestId['payment'][0]['status']['status'] ?? null) === 'APPROVED') {
                    // Actualizo el transactions, campo: installments_paid
                    $subscription->update(['installments_paid' => $subscription->installments_paid + 1]);

                    if ($subscription->paymentLinks()->first() !== null) {
                        $subscription->paymentLinks()->first()->update(['status' => 'Contrato Efectivo']);
                    }
                }

                $updatePayment = PlaceToPaySubscription::updateWith($subscription, $subscriptionByRequestId, null);

                // guardas registro primer cuota

                $result = [
                    "newPayment" => $updatePayment,
                    "response" => $requestSubscriptionById,
                    // "data" => $data,
                ];

                // creas todas las cuotas restantes, si hay
                if (($result['response']['status']['status'] ?? null) === 'APPROVED') {
                    // $responseUpdateZohoPlaceToPay = $this->zohoController->updateZohoPlaceToPay($result,$requestIdRequestSubscription);
                    $this->createRemainingInstallments($result, $requestsSubscription);
                }

                return $result;
            }
        } else {
            // Crear la primer cuota directametne
            if (!(count($requestsSubscription->subscriptions) === $requestsSubscription->quotes)) {
                //No estan creadas todas las cuotas de la suscripcion

                //empiezo pagando la primer cuota
                $result = $this->pagarCuotaSuscripcion($requestsSubscription, 1, $requestsSubscription->transaction_id);

                if (($result['response']['status']['status'] ?? null) === 'REJECTED') {
                    if (!$this->isRejectedTokenTransaction($requestsSubscription)) {
                        // Marca como invalido el token
                        $requestsSubscription->update([
                            'token_collect_para_el_pago' => 'CARD_REJECTED_' . $requestsSubscription->token_collect_para_el_pago
                        ]);
                    }
                }

                // creas todas las cuotas restantes, si hay
                if (($result['response']['status']['status'] ?? null) === 'APPROVED') {
                    // $responseUpdateZohoPlaceToPay = $this->zohoController->updateZohoPlaceToPay($result,$requestIdRequestSubscription);
                    $this->createRemainingInstallments($result, $requestsSubscription);
                }

                return $result;
            }
        }
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
    public function validateSignature($request)
    {

        $session = PlaceToPayTransaction::where(['requestId' => $request['requestId']])->get()->first();
        Log::debug('validateSignature-> Valor de $session:', [$session]);
        $string = $session->type;
        if (stripos($string, "Subscription") !== false) {
            $secretKey = $this->secret_su;
        } else {
            $secretKey = $this->secret_pu;
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
                    $success = $this->pagarCuotaSuscripcion($request, 1, $request->transaction_id);

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
            }
        }
    }
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

    public function payInstallments()
    {
        // Log::channel('placetopay')->info('Se ejecuta la regla de payInstallments.');
        // $subscriptions = PlaceToPaySubscription::where('status', '!=', 'APPROVED')->orWhereNull('status')->get();

        $subscriptions = PlaceToPaySubscription::where('status', null)->get();

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
                else {
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
                        // "returnUrl" => "https://dnetix.co/p2p/client",
                        // "ipAddress" => $request->ip(), // Usar la dirección IP del cliente
                        // "userAgent" => $request->header('User-Agent')
                    ];

                    $response = $this->billSubscription($data, $cron = true);

                    if ($response['payment'][0]['status'] ?? null !== 'APPROVED') {
                        // Actualizo el transactions, campo:
                        PlaceToPayTransaction::incrementInstallmentsPaid($request->id);
                        //PlaceToPayTransaction::find($request->id)->update(['installments_paid' => DB::raw('COALESCE(installments_paid, 0) + 1')]);
                    }

                    if (($response['status']['status'] ?? null) === 'FAILED') {
                        $paidaySubscription = PlaceToPaySubscription::find($subscription->id)->update([
                            'status' => $response['status']['status'],
                            'reason' => $response['status']['reason'],
                            'message' => $response['status']['message'],
                            'date' => $response['status']['date'],
                        ]);
                    } else {
                        // guardas registro primer cuota
                        $paidaySubscription = PlaceToPaySubscription::find($subscription->id)->update([
                            // 'transactionId' => $request->id,
                            'status' => $response['status']['status'],
                            'reason' => $response['status']['reason'],
                            'message' => $response['status']['message'],
                            'date' => $response['status']['date'],
                            'requestId' => $response['requestId'],

                            'authorization' => $response['payment'][0]['authorization'] ?? null,
                            //TODO: siempre lo veo como : 999999
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
                    //$session->approvedTokenCollect($sessionFromPTP['subscription']);

                    //Loque sigue lo maneja otra regla:
                    //Realizar el primer pago.
                    //Creacion de cuotas.

                    $zohoService = new ZohoService($this->zohoClient);
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
}
