<?php

namespace App\Http\Controllers;

use App\Helpers\Manage;
use App\Http\Requests\CreateSessionSubscriptionRequest;
use App\Models\Contact;
use App\Models\Lead;
use App\Models\PlaceToPaySubscription;
use App\Models\PlaceToPayTransaction;
use App\Services\PlaceToPay\PlaceToPayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use stdClass;

class PlaceToPayController extends Controller
{
    public $login_pu = "";
    public $secret_pu = "";
    public $login_su = "";
    public $secret_su = "";
    public $placeTopayService = null;

    public $status = [
        'FAILED' => 400,
        'APPROVED' => 200,
        'REJECTED' => 400,
        'PENDING' => 400,
        'DESCONOCIDO' => 400,
    ];
    public $message = [
        'FAILED' => 'Hubo un error con el pago de la sesion, cree otra.',
        'APPROVED' => 'Ya se ha realizado el pago de la primera cuota.',
        'REJECTED' => 'La tarjeta fue rechazada, cree otra session e ingrese denuevo los datos de la tarjeta.',
        'PENDING' => 'El estado de la peticion de la tarjeta estan pendientes.',
        'DESCONOCIDO' => 'Se desconoce el error. Mire los logs o consulte en PTP.',
    ];

    public function __construct(PlaceToPayService $placeTopayService)
    {

        $this->placeTopayService = $placeTopayService;
        $this->login_pu = env("REACT_APP_PTP_LOGIN_PU");
        $this->secret_pu = env("REACT_APP_PTP_SECRECT_PU");
        $this->login_su = env("REACT_APP_PTP_LOGIN_SU");
        $this->secret_su = env("REACT_APP_PTP_SECRECT_SU");
    }

    public function show()
    {
        $transactions = PlaceToPayTransaction::all();
        return view('ptp.subs', compact('transactions'));
    }

    public function showPaymentsOfTransaction($reference)
    {
        $completeTransaction = PlaceToPayTransaction::where('reference', $reference)->first();

        return view('ptp.completeTransaction', compact('completeTransaction'));
    }

    public function renewSession(Request $request, $reference)
    {
        $session = PlaceToPayTransaction::where(['reference' => $request->reference, 'status' => 'SUSPEND'])->first();
        $newSession = $session->replicate();
        $newSession->save();

        $soContract = $this->placeTopayService->extractSOFromReference($newSession->reference);

        $newSession->update([
            'status' => 'RENEW',
            'reason' => null,
            'message' => null,
            'requestId' => null,
            'processUrl' => null,
            'token_collect_para_el_pago' => null,
            'expiration_date' => null,
            'reference' => $this->placeTopayService->getNameReferenceSession('TEST_' . $soContract),
            'transaction_id' => $session->id
        ]);

        $session->update(['status' => 'RENEWED']);

        // Copia otros atributos si es necesario
        $lastRejected = $session->lastRejectedSubscription();
        $newFirstPayment = $lastRejected->replicate();
        $newSession->subscriptions()->save($newFirstPayment);

        $newFirstPayment->update([
            'status' => null,
            'reason' => null,
            'message' => null,
            'requestId' => null,
            'authorization' => null,
            'reference' => null,
            'date' => now(),
            'failed_payment_attempts' => 0,
            'date_to_pay' => now()
        ]);


        foreach ($session->subscriptions as $subscription) {
            if ($subscription->status === null) {
                $newSubscription = $subscription->replicate();
                // Realiza modificaciones en la nueva suscripción si es necesario
                $newSession->subscriptions()->save($newSubscription);
                $subscription->delete();
            }
        }
        return redirect()->route('ptp.home');
    }

    public function authRenewSession($reference)
    {
        $renewSession = PlaceToPayTransaction::where('reference', $reference)->where('status', 'RENEW')->first();

        return response()->json([
            "renewSession" => $renewSession,
            "quotes" => [
                "detail" => $renewSession->subscriptions,
                "count" => $renewSession->subscriptions->count(),
            ]
        ]);
    }

    public function revokeTokenSession($requestIdSession)
    {
        try {

            return response()->json([
                'result' => $this->placeTopayService->revokeTokenSession($requestIdSession)
            ]);
        } catch (\Exception $e) {
            $err = [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                // 'trace' => $e->getTraceAsString()
            ];

            Log::error("Error en pruebaregladepago: " . $e->getMessage() . "\n" . json_encode($err, JSON_PRETTY_PRINT));
            return response()->json([
                $err
            ]);
        }
    }

    public function savePaymentSubscription(Request $request)
    {

        $this->validate($request, [
            'requestId' => 'required',
        ], [
            'requestId.required' => 'El campo requestId es obligatorio.',
        ]);

        try {

            $transaction = PlaceToPayTransaction::where(['requestId' => $request->requestId])->first();

            if (isset($request->renewSuscription) && !$request->renewSuscription) {
                PlaceToPayTransaction::checkFirstPaymentWithServiceOf($transaction, $this->placeTopayService);
            }

            //Consulto a ptp la session.
            $sessionSubscription = $this->placeTopayService->getByRequestId($request->requestId, $cron = false, $transaction->isSubscription());
            $statusSession = $sessionSubscription["status"]["status"];
            //Actualizo el estado de la session.
            $transaction->update([
                'status' => $statusSession,
                'reason' => $sessionSubscription["status"]["reason"],
                'message' => $sessionSubscription["status"]["message"],
                'date' => $sessionSubscription["status"]["date"],
                'requestId' => $sessionSubscription["requestId"],
            ]);

            //Cuando la session esta APRROVED.
            $isApproveSession = PlaceToPayTransaction::checkApprovedSessionTryPay($sessionSubscription, $transaction, $this->placeTopayService, $request->renewSuscription);

            $transaction->save();


            if (isset($isApproveSession['statusPayment']) && $isApproveSession['statusPayment'] == 'PENDING') {
                    return response()->json([
                        "result" => $isApproveSession['result'],
                        "statusPayment" => $isApproveSession['statusPayment'],
                        "payment" => $isApproveSession['payment'],
                        "statusSession" => $statusSession,
                        "sessionPTP" => $sessionSubscription,
                    ]);
            }

            if (isset($isApproveSession['statusPayment']) && $isApproveSession['statusPayment'] == 'APPROVED') {
                if ( $transaction->type === 'payment' )
                    return response()->json($isApproveSession);
                $this->placeTopayService->createRemainingInstallments($isApproveSession['paymentDate'], $transaction);
                return response()->json($isApproveSession);
            }

            if (isset($isApproveSession['statusPayment']) && $isApproveSession['statusPayment'] == 'REJECTED') {
                return response()->json([
                    "result" => $isApproveSession['result'],
                    "statusPayment" => $isApproveSession['statusPayment'],
                    "payment" => $isApproveSession['payment'],
                    "statusSession" => $statusSession,
                    "sessionPTP" => $sessionSubscription,
                ]);
            }

            if (isset($isApproveSession['sessionPtp'])) {
                //Preparo las respuestas de ERROR
                $status = $statusSession ?? 'DESCONOCIDO';
                $message = $sessionSubscription['status']['message'] ?? 'DESCONOCIDO';

                if ($statusSession === 'REJECTED')
                    $message = $message . '. Cree una nueva session.';

                if ($statusSession !== "APPROVED") {
                    $status = $this->status[$statusSession] ?? 200;

                    return response()->json([
                        "result" => $message,
                        "statusSession" => $statusSession,
                        "sessionPTP" => $sessionSubscription,
                    ], $status);
                }
            }
        } catch (\Exception $e) {
            $err = [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                // 'trace' => $e->getTraceAsString()
            ];

            Log::error("Error en savePaymentSubscription: " . $e->getMessage() . "\n" . json_encode($err, JSON_PRETTY_PRINT));
            return response()->json([
                $err
            ]);
        }
    }

    public function getSessionByRequestId($requestId)
    {
        try {
            return response()->json([
                'sessionPTP' => $this->placeTopayService->getByRequestId($requestId),
                'sessionDB' => PlaceToPayTransaction::where(['requestId' => $requestId])->get()->first()
            ]);
        } catch (\Exception $e) {
            // Manejo de errores si ocurre alguno durante la solicitud

            $err = [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ];

            Log::error("Error en getSessionByRequestId: " . $e->getMessage() . "\n" . json_encode($err, JSON_PRETTY_PRINT));
            return response()->json([
                $err
            ], 500);
        }
    }

    public function renewSessionSubscription(Request $request)
    {
        $sessionToRenew = PlaceToPayTransaction::where('reference', $request['so'])->where('status', 'RENEW')->first();

        if ($sessionToRenew === null)
            return response()->json(['message' => 'La sesion que intenta renovar no tiene el estado de RENEW'], 404);

        try {
            $payer = PlaceToPaySubscription::generatePayerPaymentSession($request);
            $subscription = PlaceToPaySubscription::generateDetailPaymentSession($request['so']);

            $auth = $this->placeTopayService->generateAuthentication($isSubscription = true);
            $expiration = $this->placeTopayService->getDateExpiration();

            $data = PlaceToPaySubscription::generatePaymentDataSession($auth, $payer, $subscription, $expiration, $request);

            $result = $this->placeTopayService->create($data);

            if (isset($result['status']['status'])) {
                $sessionToRenew->update([
                    'status' => $result['status']['status'],
                    'reason' => $result['status']['reason'],
                    'message' => $result['status']['message'],
                    'date' => $result['status']['date'],
                    'requestId' => $result['requestId'],
                    'processUrl' => $this->placeTopayService->reduceUrl($result['processUrl']),
                    'reference' => $subscription['reference'],
                    'expiration_date' => $data['expiration'],
                    'paymentData' => json_encode($payer, JSON_UNESCAPED_SLASHES)
                ]);

                $getById = $this->placeTopayService->getByRequestId($result['requestId'], $cron = false, $isSubscription = true);

                if ($result['status']['status'] === 'OK') {
                    $this->placeTopayService->updateStatusSessionSubscription($request['so']);
                }
            }

            return response()->json([$result, $getById]);

        } catch (\Exception $e) {
            $err = [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                // 'trace' => $e->getTraceAsString(),
            ];

            Log::error("Error en createSessionSuscription: " . $e->getMessage() . "\n" . json_encode($err, JSON_PRETTY_PRINT));
            return response()->json([
                $err
            ], 500);
        }
    }
    public function createSessionSubscription(CreateSessionSubscriptionRequest $request)
    {
        try {

            $payer = PlaceToPaySubscription::generatePayerPaymentSession($request);
            $reference = $this->placeTopayService->getNameReferenceSession($request['so']);
            $subscription = PlaceToPaySubscription::generateDetailPaymentSession($reference);

            $auth = $this->placeTopayService->generateAuthentication($isSubscription = true);
            $expiration = $this->placeTopayService->getDateExpiration();

            $data = PlaceToPaySubscription::generatePaymentDataSession($auth, $payer, $subscription, $expiration, $request);

            $result = $this->placeTopayService->create($data);

            if (isset($result['status']['status'])) {
                PlaceToPayTransaction::create([
                    'status' => $result['status']['status'],
                    'reason' => $result['status']['reason'],
                    'message' => $result['status']['message'],
                    'date' => $result['status']['date'],
                    'requestId' => $result['requestId'],
                    'processUrl' => $this->placeTopayService->reduceUrl($result['processUrl']),
                    'total' => $request['payment']['total'],
                    'currency' => 'USD',
                    'quotes' => $request['payment']['quotes'],
                    'remaining_installments' => $request['payment']['remaining_installments'],
                    'first_installment' => ($request['payment']['first_installment'] ?? null),
                    'reference' => $subscription['reference'],
                    'type' => "requestSubscription",
                    'expiration_date' => $data['expiration'],
                    'paymentData' => json_encode($payer, JSON_UNESCAPED_SLASHES),
                    'transaction_id' => null,
                    'contract_id' => $request->contractId
                ]);

                $getById = $this->placeTopayService->getByRequestId($result['requestId'], $cron = false, $isSubscription = true);

                if ($result['status']['status'] === 'OK') {
                    $this->placeTopayService->updateStatusSessionSubscription($request['so']);
                }
            }

            return response()->json([$result, $getById]);
        } catch (\Exception $e) {
            $err = [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                // 'trace' => $e->getTraceAsString(),
            ];

            Log::error("Error en createSessionSuscription: " . $e->getMessage() . "\n" . json_encode($err, JSON_PRETTY_PRINT));
            return response()->json([
                $err
            ], 500);
        }
    }

    public function createSession(Request $request)
    {
        //TODO: Refacfor pago unico
        try {
            $payer = PlaceToPaySubscription::generatePayerPaymentSession($request);

            $reference = $this->placeTopayService->getNameReferenceSession($request['so']);
            $payment = PlaceToPaySubscription::generateDetailPaymentSession($reference);
            $payment["description"] = "Prueba Pago Unico de OceanoMedicina";
            $payment["amount"] = [
                "currency" => "USD",
                "total" => $request['payment']['total'],
            ];
            $data = [
                "auth" => $this->placeTopayService->generateAuthentication(),
                "locale" => "es_CO",
                "buyer" => $payer,
                "payment" => $payment,
                "expiration" => $this->placeTopayService->getDateExpiration(),
                "returnUrl" => "https://msklatam.com/ec/gracias",
                "ipAddress" => $request->ip(),
                // Usar la dirección IP del cliente
                "userAgent" => $request->header('User-Agent')
            ];

            $result = $this->placeTopayService->create($data);

            if (isset($result['status']['status'])) {
                $placeToPayTransaction = PlaceToPayTransaction::create([
                    'status' => $result['status']['status'],
                    'reason' => $result['status']['reason'],
                    'message' => $result['status']['message'],
                    'date' => $result['status']['date'],
                    'requestId' => $result['requestId'],
                    'processUrl' => $this->placeTopayService->reduceUrl($result['processUrl']),
                    'total' => $data['payment']['amount']['total'],
                    'currency' => $data['payment']['amount']['currency'],
                    'reference' => $data['payment']['reference'],
                    'type' => "payment",
                    'expiration_date' => $data['expiration'],
                    'paymentData' => json_encode($payer, JSON_UNESCAPED_SLASHES),
                    'contract_id' => $request->contractId
                ]);
                $getById = $this->placeTopayService->getByRequestId($result['requestId']);
                $placeToPayTransaction = PlaceToPayTransaction::where(["requestId" => $result['requestId']])
                    ->update([
                        'status' => $getById['status']['status'],
                        'reason' => $getById['status']['reason'],
                        'message' => $getById['status']['message'],
                        'date' => $getById['status']['date'],
                    ]);
            }

            return response()->json([$result, $getById]);
        } catch (\Exception $e) {
            $err = [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                // 'trace' => $e->getTraceAsString(),
            ];

            Log::error("Error en createSession: " . $e->getMessage() . "\n" . json_encode($err, JSON_PRETTY_PRINT));
            return response()->json([
                $err
            ], 500);
        }
    }
    public function notificationUpdate(Request $request)
    {
        Log::channel("slack")->warning(print_r($request->all(), true));
        if ($this->placeTopayService->validateSignature($request)) {

            PlaceToPayTransaction::where(['requestId' => $request['requestId']])
                ->update([
                    'requestId' => $request['requestId'],
                    'status' => $request['status']['status'],
                    'message' => $request['status']['message'],
                    'reason' => $request['status']['reason'],
                    'date' => $request['status']['date'],
                ]);

            $session = PlaceToPayTransaction::where(['requestId' => $request['requestId']])->get()->first();
            if ($request['status']['status'] === 'APPROVED') {
                //TODO: Realizas el primer pago si es subscripcion
            }

            return response()->json([
                'result' => 'SUCCESS',
                'message' => 'Sesion actualizada.',
                'notification' => $request,
                'session' => $session
            ]);
        }

        return response()->json([
            'result' => 'FAILED',
            'message' => 'Signature no valido.',
        ], 400);

    }

    public function updateStatusSessionSubscription($reference)
    {
        $session = PlaceToPayTransaction::where('reference', $reference)->first();

        try {
            $sessionStatusInPtp = $this->placeTopayService->getByRequestId($session->requestId, false, $session->isSubscription());

            $paymentOfSession = $session->subscriptions->first();

            $session->update([
                'status' => $session->isSubscription() ? $paymentOfSession->status: $session->status ,
                'reason' => $sessionStatusInPtp['status']['reason'],
                'message' => $sessionStatusInPtp['status']['message'],
                'date' => $sessionStatusInPtp['status']['date'],
            ]);

            return response()->json([
                'reference' => $reference,
                'updateTo' => $sessionStatusInPtp['status']['status'],
                'ptpResponse' => $sessionStatusInPtp,
                'payment' => $session->isSubscription() ? $paymentOfSession->status: $session->status
            ]);

        } catch (\Exception $e) {
            return response()->json($e, 500);
        }


    }

    // Esto es cuando se ejecuta el create sesion que es la creacion del pago unico. //Se paga a travez de la pasarela.
    public function savePayments(Request $request)
    {
        try {
            if (isset($request['requestId'])) {
                $sessionSubscription = $this->placeTopayService->getByRequestId($request['requestId']);

                $updatedDB = PlaceToPayTransaction::updateOrCreate(
                    ['requestId' => $sessionSubscription["requestId"]],
                    [
                        'status' => $sessionSubscription["status"]["status"],
                        'reason' => $sessionSubscription["status"]["reason"],
                        'message' => $sessionSubscription["status"]["message"],
                        'date' => $sessionSubscription["status"]["date"],
                        'requestId' => $sessionSubscription["requestId"],

                        // 'processUrl' => $transaction["processUrl"],
                        // 'reference' => $request["request"]["payment"]["reference"],
                        // 'currency' => $request["request"]["payment"]["amount"]["currency"],
                        // 'total' => $request["request"]["payment"]["amount"]["total"],
                        // 'contact_id' => $request["request"]["payment"]["amount"]["amount"],
                        'authorization' => $request["payment"] !== null ? $request["payment"]["authorization"] : null,
                        //si sesta pagado tiene este payment
                        // 'type' => isset($request["subscription"]) ? ///subscription o payment,
                        // 'token_collect' => $request["processUrl"],
                    ]
                );
                $updateContract = null;
                if ($sessionSubscription['status']['status'] === "APPROVED") {
                    //usar el metodo del controlador
                    // $updateContract = $this->zohoController->updateZohoPlaceToPay($request,$sessionSubscription,$sessionSubscription["requestId"]);
                }
                return response()->json([
                    $updatedDB,
                    $updateContract
                ]);
            }
        } catch (\Exception $e) {
            $err = [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                // 'trace' => $e->getTraceAsString(),
            ];

            Log::error("Error en savePayments: " . $e->getMessage() . "\n" . json_encode($err, JSON_PRETTY_PRINT));
            return response()->json([
                $err
            ]);
        }
    }

    public function getStatusByRequestId(Request $request, $request_id)
    {
        $subscriptionFromPTP = $this->placeTopayService->getByRequestId($request_id, true, true);
        $subscription = PlaceToPaySubscription::where('requestId', $request_id)->first();
        $session = $subscription->transaction->toArray();

        if ($subscriptionFromPTP['status']['status'] !== 'PENDING') {
            $subscription->update([
                'status' => $subscriptionFromPTP['status']['status'],
                'date' => $subscriptionFromPTP['status']['date'],
                'reason' => $subscriptionFromPTP['status']['reason'],
                'message' => $subscriptionFromPTP['status']['message'],
            ]);
        }

        return response()->json([
            'ptp' => $subscriptionFromPTP,
            'payment' => $subscription->toArray(),
            'session' => $session
        ]);
    }


}
