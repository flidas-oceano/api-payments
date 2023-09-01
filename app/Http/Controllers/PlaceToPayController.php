<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\PlaceToPayTransaction;
use App\Services\PlaceToPay\PlaceToPayService;
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

    public function __construct(PlaceToPayService $placeTopayService) {
        $this->placeTopayService = $placeTopayService;
        $this->login_pu = env("REACT_APP_PTP_LOGIN_PU");
        $this->secret_pu = env("REACT_APP_PTP_SECRECT_PU");
        $this->login_su = env("REACT_APP_PTP_LOGIN_SU");
        $this->secret_su = env("REACT_APP_PTP_SECRECT_SU");
    }
    public function pruebaregladepago(){
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
    public function billSubscription(Request $request, $requestId){
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

            $requestSusbcription = PlaceToPayTransaction::where([ 'requestId' => $requestId ] )->get()->first();
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
                "ipAddress" => $request->ip(), // Usar la dirección IP del cliente
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
    public function savePaymentSubscription(Request $request){
        try {
            if( isset($request['requestId']) ){
                $sessionSubscription = $this->placeTopayService->getByRequestId($request['requestId']);

                PlaceToPayTransaction::updateOrCreate(
                    [ 'requestId' => $sessionSubscription["requestId"] ],
                    [
                        'status' => $sessionSubscription["status"]["status"],
                        'reason' => $sessionSubscription["status"]["reason"],
                        'message' => $sessionSubscription["status"]["message"],
                        'date' => $sessionSubscription["status"]["date"],
                        'requestId' => $sessionSubscription["requestId"],
                    ]
                );
                if($sessionSubscription['status']['status'] === "APPROVED"){
                    if(isset($sessionSubscription['subscription'])){
                        foreach($sessionSubscription['subscription']['instrument'] as $instrument){
                            if($instrument['keyword'] === "token"){
                                PlaceToPayTransaction::updateOrCreate(
                                    [ 'requestId' => $sessionSubscription["requestId"] ],
                                    [
                                        'token_collect_para_el_pago' => $instrument['value']
                                    ]
                                );
                                // realizar primer pago de subscripcion
                                $this->placeTopayService->payFirstQuoteCreateRestQuotesByRequestId($sessionSubscription["requestId"]);
                            }
                        }
                    }
                }
            }
            return response()->json([
                "ok"
            ]);
        } catch (\Exception $e) {
            $err = [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                // 'trace' => $e->getTraceAsString()
              ];

            Log::error("Error en savePaymentSuscription: " . $e->getMessage() . "\n" . json_encode($err, JSON_PRETTY_PRINT));
            return response()->json([
                $err
            ]);
        }
    }
    public function updateSession(){
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
    public function getSessionByRequestId($requestId)
    {
        try {
            return response()->json(
                $this->placeTopayService->getByRequestId($requestId)
            );
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
    public function createSessionSubscription(Request $request)
    {
        // "payment": {
        //     "reference": "PAY_ABC_1287",
        //     "description": "Pago por Placetopay",
        //     "amount": {
        //         "currency": "USD",
        //         "total": 1000
        //     },
        //     "subscribe": true
        // }


        // {
        //     "su":{
        //      "total" 20000:,
        //      "cant_cuotas": 10,
        //      "valor_cuota": 2000,
        //      "currency": "USD",
        //     },
        //     "sup":{ //20000/10 = 2k x mes
        //         "total"20000: ,
        //         "cant_cuotas": 10,
        //         "primer_cuota": 1000,
        //         "valor_cuota": 2111,
        //         "currency": "USD",
        //     }
        // }

        $payer = [
            "name" => $request['payer']['name'],
            "surname" => $request['payer']['surname'],
            "email" => $request['payer']['email'],
            "document" => $request['payer']['document'],
            "documentType" => "CC",
        ];
        $subscription = [
            "reference" => $request['so'],
            "description" => "Prueba suscripcion contrato de OceanoMedicina"
        ];
        $data = [
            "auth" => $this->placeTopayService->generateAuthentication(),
            "locale" => "es_CO",
            "payer" => $payer,
            "subscription" => $subscription,
            "expiration" => $this->placeTopayService->getDateExpiration(),
            "returnUrl" => "https://dnetix.co/p2p/client",
            "ipAddress" => $request->ip(), // Usar la dirección IP del cliente
            "userAgent" => $request->header('User-Agent')
        ];

        try {
            $result = $this->placeTopayService->create($data);

            if (isset($result['status']['status'])) {
                $placeToPayTransaction = PlaceToPayTransaction::create([
                    'status' =>             $result['status']['status'],
                    'reason' =>             $result['status']['reason'],
                    'message' =>            $result['status']['message'],
                    'date' =>               $result['status']['date'],
                    'requestId' =>          $result['requestId'],
                    'processUrl' =>         $this->placeTopayService->reduceUrl($result['processUrl']),

                    'total' => $request['payment']['total'],
                    'currency' => 'USD',
                    'quotes' => $request['payment']['quotes'],
                    'remaining_installments' => $request['payment']['amount'],

                    // 'contact_id' => ,
                    // 'authorization' => ,

                    'reference' => $request['so'],
                    'type' => "requestSubscription",
                    // 'token_collect_para_el_pago' => ,
                    'expiration_date' =>    $data['expiration'],
                ]);
                $getById = $this->placeTopayService->getByRequestId($result['requestId']);
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
            $lead =Lead::updateOrCreate(
                [ 'email' => $request['payer']['email'] ],
            [
                'name' => $request['payer']['email'],
                'username' => $request['payer']['surname'],
                'email' => $request['payer']['email'],
            ]);
            $contact = Contact::updateOrCreate(
                [ 'id' => $lead->contact_id ],
            [
                'dni' => $request['payer']['document'],
            ]);

            $lead->contact_id = $contact->id;
            $lead->save();

            $payer = [
                "name" => $request['payer']['name'],
                "surname" => $request['payer']['surname'],
                "email" => $request['payer']['email'],
                "document" => $request['payer']['document'],
                "documentType" => "CC",
            ];
            $payment = [
                "reference" => $request['so'],
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
            "ipAddress" => $request->ip(), // Usar la dirección IP del cliente
            "userAgent" => $request->header('User-Agent')
        ];

            $result = $this->placeTopayService->create($data);

            if (isset($result['status']['status'])) {
                $placeToPayTransaction = PlaceToPayTransaction::create([
                    'status' =>             $result['status']['status'],
                    'reason' =>             $result['status']['reason'],
                    'message' =>            $result['status']['message'],
                    'date' =>               $result['status']['date'],
                    'requestId' =>          $result['requestId'],
                    'processUrl' =>         $this->placeTopayService->reduceUrl($result['processUrl']),
                    'contact_id' =>         $lead->contact_id,
                    // 'authorization' => ,
                    'total' =>              $data['payment']['amount']['total'],
                    'currency' =>           $data['payment']['amount']['currency'],
                    'reference' =>          $data['payment']['reference'],
                    'type' => "payment",
                    // 'token_collect_para_el_pago' => ,
                    'expiration_date' =>    $data['expiration'],
                ]);
                $getById = $this->placeTopayService->getByRequestId($result['requestId']);
                $placeToPayTransaction = PlaceToPayTransaction::where(["requestId" => $result['requestId']])
                ->update([
                    'status' =>             $getById['status']['status'],
                    'reason' =>             $getById['status']['reason'],
                    'message' =>            $getById['status']['message'],
                ]);
            }

            // Aquí puedes procesar la respuesta como desees
            // Por ejemplo, devolverla como una respuesta JSON
            return response()->json([$result,$getById]);
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

    //actualizar zoho.
    //TODO: con ptp en el controlador de zoho.

    public function createPayment(){
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
            "auth":'. $this->placeTopayService->generateAuthentication() .'
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
    public function GetPaymentByRequestId(){
        // (pagounico)
        $jsonPaymentByIdRequest = '{
            "auth":'. $this->placeTopayService->generateAuthentication() .'
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

    // Esto es cuando se ejecuta el create sesion que es la creacion del pago unico. //Se paga a travez de la pasarela.
    public function savePayments(Request $request){
    try {

      foreach($request->transactions as $transaction){
        if($transaction["status"]["status"] === "OK"){
          //esta ok
          PlaceToPayTransaction::updateOrCreate(
            ['requestId' => $transaction["requestId"]],
            [
              'status' => $transaction["status"]["status"],
              'reason' => $transaction["status"]["reason"],
              'message' => $transaction["status"]["message"],
              'date' => $transaction["status"]["date"],
              'requestId' => $transaction["requestId"],
              // 'processUrl' => $transaction["processUrl"],
              'reference' => $request["request"]["payment"]["reference"],
              'currency' => $request["request"]["payment"]["amount"]["currency"],
              'total' => $request["request"]["payment"]["amount"]["total"],
              'contact_id' => $request["request"]["payment"]["amount"]["amount"],
              'authorization' => $request["payment"] !== null ? $request["payment"]["authorization"] : null,//si sesta pagado tiene este payment
              // 'type' => isset($request["subscription"]) ? ///subscription o payment,
              // 'token_collect' => $request["processUrl"],
          ]);
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

      Log::error("Error en savePayments: " . $e->getMessage() . "\n" . json_encode($err, JSON_PRETTY_PRINT));
      return response()->json([
          $err
      ]);
    }
    }
    //requestId() payment unico
    public function requestId(Request $request){
    try {

      // ver si en el body de request tengo subscription o payment y ponerlo en el type
      foreach($request->transactions as $transaction){
        if($transaction["status"]["status"] === "OK"){
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
          ]);
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

    public function pasounocreatesession(){
    $createRequest =  json_decode('{
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
    $placeToPayTransactions->requestId  = $responseCreateRequest->requestId;
    $placeToPayTransactions->status     = $responseCreateRequest->status->status;
    $placeToPayTransactions->date       = $responseCreateRequest->status->date; // solo se guarda cuando se crea la transaccion ok. y cuando cambia de estado definitivo approved, rejected,etc.
    $placeToPayTransactions->reason     = $responseCreateRequest->status->reason;
    $placeToPayTransactions->message    = $responseCreateRequest->status->message;
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
    $placeToPayTransactions->requestId  = $responsePendingGetRequestInformation->requestId;
    $placeToPayTransactions->status     = $responsePendingGetRequestInformation->status->status;
    $placeToPayTransactions->date       = $responsePendingGetRequestInformation->status->date; //se guarda el estado porque tiene que ser pagado.
    $placeToPayTransactions->reason     = $responsePendingGetRequestInformation->status->reason;
    $placeToPayTransactions->message    = $responsePendingGetRequestInformation->status->message;
    $placeToPayTransactions->currency   = $responsePendingGetRequestInformation->request->payment->amount->currency;
    $placeToPayTransactions->total      = $responsePendingGetRequestInformation->request->payment->amount->total;
    $placeToPayTransactions->reference  = $responsePendingGetRequestInformation->request->payment->reference; // enrealidad buscar el buyer en contactos.
    $placeToPayTransactions->requestId  = $responsePendingGetRequestInformation->requestId;

    $createRequest2 =  json_decode('{
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
    public function subscription (){
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
}
