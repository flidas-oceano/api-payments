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
    public $zohoController = null;

    public function __construct(PlaceToPayService $placeTopayService, ZohoController $zohoController) {
        $this->zohoController = $zohoController;
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
        // // "payment": {
        //     "reference": "PAY_ABC_1287",
        //     "description": "Pago por Placetopay",
        //     "amount": {
        //         "currency": "USD",
        //         "total": 1000
        //     },
        //     "subscribe": true
        // //}

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

        //comprador - el que recibe el curso
        $buyer = [

        ];
        //pagador - el que paga
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

            $payer = [
                "name" => $request['payer']['name'],
                "surname" => $request['payer']['surname'],
                "email" => $request['payer']['email'],
                "document" => $request['payer']['document'],
                "documentType" => $request['payer']['documentType'], // CI - Cédula de identidad - '/^\d{10}$/' // RUC - Registro Único de Contribuyentes - '/^\d{13}$/'
                "address" => [
                    // "country" => $request['country'],
                    // "state" => $request['state'],
                    // "city" => $request['city'],
                    // "postalCode" => $request['postalCode'],
                    "street" => $request['payer']['address']['street'],
                    // "phone" => $request['phone'],
                ]
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
                    // 'contact_id' =>         $lead->contact_id,
                    // 'lead_id' =>            $lead->id,
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

    // Esto es cuando se ejecuta el create sesion que es la creacion del pago unico. //Se paga a travez de la pasarela.
    public function savePayments(Request $request){
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

                        // 'processUrl' => $transaction["processUrl"],
                        'reference' => $request["request"]["payment"]["reference"],
                        'currency' => $request["request"]["payment"]["amount"]["currency"],
                        'total' => $request["request"]["payment"]["amount"]["total"],
                        'contact_id' => $request["request"]["payment"]["amount"]["amount"],
                        'authorization' => $request["payment"] !== null ? $request["payment"]["authorization"] : null,//si sesta pagado tiene este payment
                        // 'type' => isset($request["subscription"]) ? ///subscription o payment,
                        // 'token_collect' => $request["processUrl"],
                    ]
                );
                if($sessionSubscription['status']['status'] === "APPROVED"){

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
            // 'trace' => $e->getTraceAsString(),
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


}
