<?php

namespace App\Http\Controllers;

use App\Helpers\Manage;
use App\Http\Requests\CreateGenerateLinkRequest;
use App\Models\PaymentLink;
use App\Models\PlaceToPayPaymentLink;
use App\Models\PlaceToPayTransaction;
use App\Models\RebillCustomer;
use App\Services\PlaceToPay\PlaceToPayService;
use Faker\Provider\ar_EG\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PlaceToPayPaymentLinkController extends Controller
{
    protected $placeToPayService;

    public function __construct(PlaceToPayService $placeToPayService)
    {
        $this->placeToPayService = $placeToPayService;
    }

    // public function createSessionSubscription(CreateSessionSubscriptionRequest $request)
    public function createSessionSubscription(Request $request)
    {
        //comprador - el que recibe el curso
        $payer = [
            "name" => $request['payer']['name'],
            "surname" => $request['payer']['surname'],
            "email" => $request['payer']['email'],
            "document" => $request['payer']['document'],
            "documentType" => $request['payer']['documentType'],
            "mobile" => $request['payer']['mobile'],
            // "address" => [ //domicilio
            //     // "country" => $request['country'],
            //     // "state" => $request['state'],
            //     // "city" => $request['city'],
            //     // "postalCode" => $request['postalCode'],
            //     "street" => $request['payer']['address']['street'],
            //     // "phone" => $request['phone'],//+573214445566
            // ]
        ];
        $subscription = [
            "reference" => $request['so'],
            "description" => "Prueba suscripcion contrato de OceanoMedicina"
        ];
        $data = [
            "auth" => $this->placeToPayService->generateAuthentication(),
            "locale" => "es_CO",
            "payer" => $payer,
            "subscription" => $subscription,
            "expiration" => $this->placeToPayService->getDateExpiration(),
            "returnUrl" => "https://dnetix.co/p2p/client",
            "ipAddress" => $request->ip(),
            // Usar la dirección IP del cliente
            "userAgent" => $request->header('User-Agent')
        ];

        try {
            $result = $this->placeToPayService->create($data);

            if (isset($result['status']['status'])) {
                $placeToPayTransaction = PlaceToPayTransaction::create([
                    'status' => $result['status']['status'],
                    'reason' => $result['status']['reason'],
                    'message' => $result['status']['message'],
                    'date' => $result['status']['date'],
                    'requestId' => $result['requestId'],
                    'processUrl' => $this->placeToPayService->reduceUrl($result['processUrl']),
                    'total' => $request['payment']['total'],
                    'currency' => 'USD',
                    'quotes' => $request['payment']['quotes'],
                    'remaining_installments' => $request['payment']['remaining_installments'],
                    'reference' => $request['so'],
                    'type' => "requestSubscription",
                    'expiration_date' => $data['expiration'],
                    'contract_id' => $request->contractId
                ]);
                $getById = $this->placeToPayService->getByRequestId($result['requestId']);
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

    public function create(CreateGenerateLinkRequest $request)
    {

        try {
            //estos datos se usan para actualizar o crear el contacto en db // $rebillCustomerData = $request->only(['email', 'phone', 'personalId', 'address', 'fullName', 'zip']);
            // mandame la informacion de datos personalescuando crees la susbscripcion. No se puede actualizar datos

            //obtener datos personales
            $ptpTransaction = PlaceToPayTransaction::where('requestId', $request['requestId'])->first();
            $objetoStdClass = $this->placeToPayService->getByRequestId($request['requestId'], false, $ptpTransaction->isSubscription());
            // $objetoStdClass = $placeToPayService->getByRequestId(677217);
            // Convertir el objeto stdClass en un objeto PHP
            $transactionByRequestId = json_decode(json_encode($objetoStdClass), false);

            //paymentLink data
            $paymentLinkData = $request->only(['gateway', 'type', 'contract_entity_id', 'contract_so', 'status', 'quotes', 'country']);
            $paymentLinkData['transactionId'] = $ptpTransaction->id;

            $paymentLink = PlaceToPayPaymentLink::create($paymentLinkData);

            return response()->json([
                "transactionByRequestId" => $transactionByRequestId,
                "payment" => $paymentLink,
                "session" => $paymentLink->transaction,
                "processURL" => $ptpTransaction->processUrl,
                "type" => "paymentLink"
            ]);
        } catch (\Exception $e) {
            $err = [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ];

            //Log::error("Error en PlaceToPayPaymentLinkController-create: " . $e->getMessage() . "\n" . json_encode($err, JSON_PRETTY_PRINT));

            return response()->json([
                $err
            ], 400);
        }
    }

    public function getPaymentLink(Request $request, $saleId)
    {
        try {
            // Obtener el registro más nuevo primero
            $lastPaymentPTP = PlaceToPayPaymentLink::where('contract_entity_id', $saleId)
                ->latest() // Ordenar por el campo temporal más reciente (created_at, updated_at, etc.)
                ->first(); // Obtener el registro más nuevo

            $responseJson = ["payer" => null, "checkout" => null, "payment" => null, 'previusPayment' => null];

            $responseJson['payer'] = $lastPaymentPTP->transaction->paymentData;
            $responseJson['checkout'] = $lastPaymentPTP;

            if($lastPaymentPTP->transaction->installments_paid === 0){////No tiene pago
                if($lastPaymentPTP->transaction->isOneTimePayment()){
                    $responseJson['payment'] = $lastPaymentPTP->transaction;
                }else{
                    $responseJson['payment'] = $lastPaymentPTP->transaction->subscriptions->first();
                }
            }else{//tiene pago
                if($lastPaymentPTP->transaction->isOneTimePayment()){
                    $installments_paid = $lastPaymentPTP->transaction->installments_paid;
                    if($installments_paid !== null && $installments_paid !== 0 ){
                        $responseJson['payment'] = $lastPaymentPTP->transaction;
                    }
                }else {
                    $paymentOfLink = $lastPaymentPTP->transaction->subscriptions->first();
                    if($paymentOfLink === null && $lastPaymentPTP->transaction->status === 'REJECTED'){
                        $responseJson['payment'] = $lastPaymentPTP->transaction;
                    }else{
                        $responseJson['payment'] = $paymentOfLink;
                    }
                }
            }

             // Obtener todos los registros excepto el más nuevo
            $previusPaymentsPTP = PlaceToPayPaymentLink::where('contract_entity_id', $saleId)
             ->where('id', '!=', $lastPaymentPTP->id)
             ->get();

            if($previusPaymentsPTP !== null ){
                foreach($previusPaymentsPTP as $paymentLink){
                    $responseJson['previusPayment'][] = $paymentLink->transaction;
                }
            }

            return response()->json($responseJson);

        } catch (\Exception $e) {
            $err = [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                // 'trace' => $e->getTraceAsString(),
            ];

            Log::error("Error en PlaceToPayPaymentLinkController-getPaymentLink: " . $e->getMessage() . "\n" . json_encode($err, JSON_PRETTY_PRINT));
            return response()->json([
                $err
            ]);
        }
    }

    public function updatePaymentLinkStatus(Request $request, $saleId)
    {
        try {
            // $paymentLinkPTP = PlaceToPayPaymentLink::where('contract_entity_id', $saleId)->first();
            $paymentLink = PlaceToPayPaymentLink::find($request['paymentLink']['id'])->update(['']);
            $requestSubscriptionById = $this->getByRequestId($paymentLink->transaction->requestId);
            if (($requestSubscriptionById->status->status ?? null) === 'REJECTED') {
                //acutalizo el payment link registo
                PlaceToPayPaymentLink::find($paymentLink->id)->update(['status' => $requestSubscriptionById->status->status]);
                //acutalizo el transaction a rechazado
                $paymentLink->transaction->update([
                    'status' => $requestSubscriptionById['status']['status'],
                    'reason' => $requestSubscriptionById['status']['reason'],
                    'message' => $requestSubscriptionById['status']['message'],
                ]);

                //creo creo un nuevo registro paymentlink //duplicando el registro.
                // //creo una nueva transaccion. con el r_{numero de intento}
                // tener en cuenta que el reference va a ser asi:
                // 2000339000617515005 -> REJECTED
                // 2000339000617515005_R_1 -> REJECTED
                // 2000339000617515005_R_2 -> APROVEED

                //asociarlos


                // $result = $this->placeTopayService->create($data);

                // if (isset($result['status']['status'])) {
                //     $placeToPayTransaction = PlaceToPayTransaction::create([
                //         'status' => $result['status']['status'],
                //         'reason' => $result['status']['reason'],
                //         'message' => $result['status']['message'],
                //         'date' => $result['status']['date'],
                //         'requestId' => $result['requestId'],
                //         'processUrl' => $this->placeTopayService->reduceUrl($result['processUrl']),
                //         // 'contact_id' =>         $lead->contact_id,
                //         // 'lead_id' =>            $lead->id,
                //         // 'authorization' => ,
                //         'total' => $data['payment']['amount']['total'],
                //         'currency' => $data['payment']['amount']['currency'],
                //         'reference' => $data['payment']['reference'],
                //         'type' => "payment",
                //         // 'token_collect_para_el_pago' => ,
                //         'expiration_date' => $data['expiration'],
                //     ]);
                //     $getById = $this->placeTopayService->getByRequestId($result['requestId']);
                //     $placeToPayTransaction = PlaceToPayTransaction::where(["requestId" => $result['requestId']])
                //         ->update([
                //             'status' => $getById['status']['status'],
                //             'reason' => $getById['status']['reason'],
                //             'message' => $getById['status']['message'],
                //         ]);
                // }
            }
        } catch (\Exception $e) {
            $err = [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                // 'trace' => $e->getTraceAsString(),
            ];

            Log::error("Error en PlaceToPayPaymentLinkController-getPaymentLink: " . $e->getMessage() . "\n" . json_encode($err, JSON_PRETTY_PRINT));
            return response()->json([
                $err
            ]);
        }
    }
}
