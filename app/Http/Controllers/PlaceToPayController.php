<?php

namespace App\Http\Controllers;

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
    public $zohoController = null;

    public $status= [
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

    public function __construct(PlaceToPayService $placeTopayService, ZohoController $zohoController)
    {
        $this->zohoController = $zohoController;
        $this->placeTopayService = $placeTopayService;
        $this->login_pu = env("REACT_APP_PTP_LOGIN_PU");
        $this->secret_pu = env("REACT_APP_PTP_SECRECT_PU");
        $this->login_su = env("REACT_APP_PTP_LOGIN_SU");
        $this->secret_su = env("REACT_APP_PTP_SECRECT_SU");
    }
    public function revokeTokenSession($requestIdSession){
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

    public function pruebaregladepago()
    {
        try {
            $this->placeTopayService->createInstallments();
            // $this->payInstallmentsSubscriptions();
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

    public function billSubscription(Request $request, $requestId)
    {
        try {
            // {
            //     so: ASD31813D3,
            //     payer:{
            //         "name" => "1122334455",
            //         "surname" => "Prueba",
            //         "email" => "facundobrizuela@oceano.com.ar",
            //         "document" => "1758859431",
            //         "documentType" => "CC",//harcodeado
            //     }
            //     pu:{ //null
            //         total
            //         currency
            //     }
            //     su{ //null
            //         total
            //         cant
            //         currency
            //     }
            //     sup{//null
            //         total
            //         primermonto
            //         cuotas restantes
            //         currency
            //     }
            // }

            $requestSusbcription = PlaceToPayTransaction::where(['requestId' => $requestId])->get()->first();
            $data = [
                "auth" => $this->placeTopayService->generateAuthentication(),
                "locale" => "es_CO",
                "payer" => [
                    "name" => "1122334455",
                    "surname" => "Prueba",
                    "email" => "facundobrizuela@oceano.com.ar",
                    "document" => "1758859431",
                    "documentType" => "CC",
                ],
                "payment" => [
                    "reference" => "1122334455",
                    "description" => "Prueba",
                    "amount" => [
                        "currency" => "USD",
                        "total" => 455
                    ]
                ],
                "instrument" => [
                    "token" => [
                        "token" => $requestSusbcription->token_collect_para_el_pago
                    ]
                ],
                "expiration" => $this->placeTopayService->getDateExpiration(),
                "returnUrl" => "https://dnetix.co/p2p/client",
                "ipAddress" => $request->ip(),
                // Usar la dirección IP del cliente
                "userAgent" => $request->header('User-Agent')
            ];

            $response = $this->placeTopayService->billSubscription($data);
            // Aquí puedes procesar la respuesta como desees
            // Por ejemplo, devolverla como una respuesta JSON
            return response()->json($response);
        } catch (\Exception $e) {
            $err = [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                // 'trace' => $e->getTraceAsString()
            ];

            Log::error("Error en billSubscription: " . $e->getMessage() . "\n" . json_encode($err, JSON_PRETTY_PRINT));
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

            $requestsTransaction = PlaceToPayTransaction::where(['requestId' => $request['requestId']['requestId']])->get()->first();
            if (count($requestsTransaction->subscriptions) > 0) {//Las subscripciones se crean solo si se aprobo el primer pago.

                $firstSubscription = $requestsTransaction->subscriptions->first();
                if( $firstSubscription->status==='APPROVED'){
                    return response()->json([
                        "result" => $this->message[$firstSubscription->status],
                    ], 400);
                }

                $sessionSubscription = $this->placeTopayService->getByRequestId($firstSubscription->requestId);

                if( ($sessionSubscription['status']['status'] ?? 'DESCONOCIDO') === 'PENDING')
                    $statusPayment = $sessionSubscription['status']['status'];

                if( isset( $sessionSubscription['payment'][0]['status']['status'] ) )
                    $statusPayment =  $sessionSubscription['payment'][0]['status']['status'] ?? 'DESCONOCIDO' ;

                if($statusPayment!=='APPROVED'){
                    //Actualizar estado
                    PlaceToPaySubscription::where(['id' => $firstSubscription->id ])->update([
                        'date' => $sessionSubscription['status']['date'],
                        'status' => $sessionSubscription['status']['status'],
                        'reason' => $sessionSubscription['status']['reason'],
                        'message' => $sessionSubscription['status']['message'],
                        'authorization' => $sessionSubscription['payment'][0]['authorization'] ?? null, //TODO: siempre lo veo como : 999999
                        'reference' => $response['payment'][0]['reference'] ?? null,
                    ]);

                    if($statusPayment==='REJECTED'){
                        //borrar subscripcion y transaccion.
                        if( !$this->placeTopayService->isRejectedTokenTransaction($requestsTransaction) ){
                            $requestsTransaction->update([
                                'token_collect_para_el_pago' => 'CARD_REJECTED_'.$requestsTransaction->token_collect_para_el_pago
                            ]);
                        }
                    }

                    return response()->json([
                        "result" => $this->message[$statusPayment],
                        "statusPayment" => $statusPayment,

                    ], 400);
                }

            }

            $sessionSubscription = $this->placeTopayService->getByRequestId($request['requestId']['requestId']);

            $updateRequestSession = PlaceToPayTransaction::updateOrCreate(
                ['requestId' => $sessionSubscription["requestId"]],
                [
                    'status' => $sessionSubscription["status"]["status"],
                    'reason' => $sessionSubscription["status"]["reason"],
                    'message' => $sessionSubscription["status"]["message"],
                    'date' => $sessionSubscription["status"]["date"],
                    'requestId' => $sessionSubscription["requestId"],
                ]
            );

            if ($sessionSubscription['status']['status'] === "APPROVED") {
                if (isset($sessionSubscription['subscription'])) {
                    foreach ($sessionSubscription['subscription']['instrument'] as $instrument) {
                        if ($instrument['keyword'] === "token") {
                            PlaceToPayTransaction::updateOrCreate(
                                ['requestId' => $sessionSubscription["requestId"]],
                                [
                                    'token_collect_para_el_pago' => $instrument['value']
                                ]
                            );
                            // realizar primer pago de subscripcion
                            $result = $this->placeTopayService->payFirstQuoteCreateRestQuotesByRequestId($sessionSubscription["requestId"]);

                            if( ($result['response']['status']['status'] ?? 'DESCONOCIDO') === 'PENDING')
                                $statusPayment = $result['response']['status']['status'];

                            if( isset( $result['response']['payment'][0]['status']['status'] ) )
                                $statusPayment =  $result['response']['payment'][0]['status']['status'] ?? 'DESCONOCIDO' ;

                            $this->message['APPROVED'] = 'Se ha realizado el pago con exito.';

                            $status = $this->status[$statusPayment] ?? 200;

                            return response()->json([
                                "updateRequestSession" => $updateRequestSession,
                                "result" => $this->message[$statusPayment],
                                "statusPayment" => $statusPayment,
                            ], $status);
                        }
                    }
                }
            }

            $status = $sessionSubscription['status']['status'] ?? 'DESCONOCIDO';
            $message = $sessionSubscription['status']['message'] ?? 'DESCONOCIDO';
            if($status === 'REJECTED')
                $message = $message . '. Cree una nueva session.';

            if ($status !== "APPROVED") {

                $status = $this->status[$statusPayment] ?? 200;

                return response()->json([
                    "result" => $message,
                    "statusSession" => $status,
                    "sessionPTP" => $sessionSubscription,
                ], $status);
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
                'sessionDB' => PlaceToPayTransaction::where([ 'requestId' => $requestId ])->get()->first()
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
    public function createSessionSubscription(CreateSessionSubscriptionRequest $request)
    {
        try {

            //Actualizar Estado ed la session en DB
            $this->placeTopayService->updateStatusSessionSubscription($request['SO']);

            $lastRequestSessionDB = PlaceToPayTransaction::where('reference', 'LIKE', '%' . $request['SO'] . '%')
                ->orderBy('created_at', 'desc')
                ->first();

            //Validar si hay registros con ese SO.
            // if($this->placeTopayService->canCreateSession($request['SO'])['canCreateSession']){
            //     // crear
            // }else{
            //     //no crear
            // }

            //Crear nueva Session
            //pagador - el que paga
            $payer = [
                "name" => $request['payer']['name'],
                "surname" => $request['payer']['surname'],
                "email" => $request['payer']['email'],
                "document" => $request['payer']['document'],
                "documentType" => $request['payer']['documentType'],
                "mobile" => $request['payer']['mobile'],
                "address" => [ //domicilio
                    "country" => $request['country'],
                //     // "state" => $request['state'],
                //     // "city" => $request['city'],
                //     // "postalCode" => $request['postalCode'],
                    "street" => $request['payer']['address']['street'],
                //     // "phone" => $request['phone'],//+573214445566
                ]
            ];
            $subscription = [
                "reference" => $this->placeTopayService->getNameReferenceSession($request['so']),
                // "reference" => $request['so'],
                "description" => "Prueba suscripcion contrato de OceanoMedicina"
            ];
            $data = [
                "auth" => $this->placeTopayService->generateAuthentication(),
                "locale" => "es_CO",
                "payer" => $payer,
                "subscription" => $subscription,
                "expiration" => $this->placeTopayService->getDateExpiration(),
                "returnUrl" => "https://dnetix.co/p2p/client",
                "ipAddress" => $request->ip(),
                // Usar la dirección IP del cliente
                "userAgent" => $request->header('User-Agent')
            ];

            $result = $this->placeTopayService->create($data);

            // Convertir el arreglo $payer en formato JSON

            if (isset($result['status']['status'])) {
                $placeToPayTransaction = PlaceToPayTransaction::create([
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
                    // 'contact_id' => ,
                    // 'authorization' => ,
                    'reference' => $subscription['reference'],
                    'type' => "requestSubscription",
                    // 'token_collect_para_el_pago' => ,
                    'expiration_date' => $data['expiration'],
                    'paymentData' => $jsonData = json_encode($payer, JSON_UNESCAPED_SLASHES)

                ]);
                $getById = $this->placeTopayService->getByRequestId($result['requestId']);
                if ($result['status']['status'] === 'OK') {
                    $this->placeTopayService->updateStatusSessionSubscription($request['SO']);
                }
            }

            // Aquí puedes procesar la respuesta como desees
            // Por ejemplo, devolverla como una respuesta JSON
            return response()->json([$result, $getById]);
        } catch (\Exception $e) {
            // Manejo de errores si ocurre alguno durante la solicitud

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
        try {
            // $lead = Lead::updateOrCreate(
            //     [ 'email' => $request['payer']['email'] ],
            //     [
            //         'name' => $request['payer']['email'],
            //         'username' => $request['payer']['surname'],
            //         'email' => $request['payer']['email'],
            //     ]
            // );
            // $contact = Contact::updateOrCreate(
            //     [ 'id' => $lead->contact_id ],
            //     [
            //         'dni' => $request['payer']['document'],
            //     ]
            // );

            // $lead->contact_id = $contact->id;
            // $lead->save();

            // CI - Cédula de identidad - '/^\d{10}$/' // RUC - Registro Único de Contribuyentes - '/^\d{13}$/'

            $payer = [
                "name" => $request['payer']['name'],
                "surname" => $request['payer']['surname'],
                "email" => $request['payer']['email'],
                "document" => $request['payer']['document'],
                "documentType" => $request['payer']['documentType'],
                "mobile" => $request['payer']['mobile'],
                "address" => [
                    //domicilio
                    // "country" => $request['country'],
                    // "state" => $request['state'],
                    // "city" => $request['city'],
                    // "postalCode" => $request['postalCode'],
                    "street" => $request['payer']['address']['street'],
                    // "phone" => $request['phone'],//+573214445566
                ]
            ];
            $payment = [

                "reference" => $this->placeTopayService->getNameReferenceSession($request['so']),
                // "reference" => $request['so'],
                "description" => "Prueba contrato de OceanoMedicina",
                "amount" => [
                    "currency" => "USD",
                    "total" => $request['payment']['total'],
                ]
            ];
            $data = [
                "auth" => $this->placeTopayService->generateAuthentication(),
                "locale" => "es_CO",
                "payer" => $payer,
                "payment" => $payment,
                "expiration" => $this->placeTopayService->getDateExpiration(),
                "returnUrl" => "https://dnetix.co/p2p/client",
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
                    // 'contact_id' =>         $lead->contact_id,
                    // 'lead_id' =>            $lead->id,
                    // 'authorization' => ,
                    'total' => $data['payment']['amount']['total'],
                    'currency' => $data['payment']['amount']['currency'],
                    'reference' => $data['payment']['reference'],
                    'type' => "payment",
                    // 'token_collect_para_el_pago' => ,
                    'expiration_date' => $data['expiration'],
                ]);
                $getById = $this->placeTopayService->getByRequestId($result['requestId']);
                $placeToPayTransaction = PlaceToPayTransaction::where(["requestId" => $result['requestId']])
                    ->update([
                        'status' => $getById['status']['status'],
                        'reason' => $getById['status']['reason'],
                        'message' => $getById['status']['message'],
                    ]);
            }

            // Aquí puedes procesar la respuesta como desees
            // Por ejemplo, devolverla como una respuesta JSON
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
    public function index()
    {
        // Generar autenticación
        $auth = $this->placeTopayService->generateAuthentication();

        // return response()->json($auth);

        // Datos de la solicitud
        $data = '{
            "auth": ' . json_encode($auth) . ',
            "payment": {
                "reference": "1234567890",
                "description": "Testing Payment",
                "amount": {
                    "currency": "COP",
                    "total": 3000
                }
            },
            "instrument": {
                "card": {
                    "number": "4110760000000008"
                }
            },
            "ipAddress": "127.0.0.1",
            "userAgent": "Testing"
        }';

        try {
            $client = new Client();
            $response = $client->post('https://checkout-test.placetopay.ec/api/session', [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'body' => $data,
            ]);

            // Imprimir el resultado de la consulta
            echo $response->getBody();
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            // En caso de error en la solicitud
            echo "Error: " . $e->getMessage();
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
                        'authorization' => $request["payment"] !== null ? $request["payment"]["authorization"] : null, //si sesta pagado tiene este payment
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
    //requestId() payment unico
    public function requestId(Request $request)
    {
        try {

            // ver si en el body de request tengo subscription o payment y ponerlo en el type
            foreach ($request->transactions as $transaction) {
                if ($transaction["status"]["status"] === "OK") {
                    //esta ok
                    PlaceToPayTransaction::create(
                        ['requestId' => $transaction["requestId"]],
                        [
                            'status' => $transaction["status"]["status"],
                            'reason' => $transaction["status"]["reason"],
                            'message' => $transaction["status"]["message"],
                            'date' => $transaction["status"]["date"],
                            'requestId' => $transaction["requestId"],
                            'processUrl' => $transaction["processUrl"],
                            // 'contact_id' => $request->,
                            // 'authorization' => $request->,
                            // 'total' => $request->,
                            // 'currency' => $request->,
                            // 'reference' => $request->,
                            // 'type' => $request->,
                            // 'token_collect' => $request->,
                        ]
                    );
                }
            }

            return response()->json([
                // $arrayRequest,
                // $arrayResponse
            ]);

        } catch (\Exception $e) {
            $err = [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTraceAsString(),
            ];

            Log::error("Error en PopulateProducts: " . $e->getMessage() . "\n" . json_encode($err, JSON_PRETTY_PRINT));
            return response()->json([
                $err
            ]);
        }
    }

}
