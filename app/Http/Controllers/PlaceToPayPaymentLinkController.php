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
        $buyer = [
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

                    // 'contact_id' => ,
                    // 'authorization' => ,

                    'reference' => $request['so'],
                    'type' => "requestSubscription",
                    // 'token_collect_para_el_pago' => ,
                    'expiration_date' => $data['expiration'],
                ]);
                $getById = $this->placeToPayService->getByRequestId($result['requestId']);
            }

            // Aquí puedes procesar la respuesta como desees
            // Por ejemplo, devolverla como una respuesta JSON
            return response()->json([$result, $getById]);
        } catch (\Exception $e) {
            // Manejo de errores si ocurre alguno durante la solicitud

            Manage::error($e);
        }
    }

    public function create(CreateGenerateLinkRequest $request)
    {

        try {

            //estos datos se usan para actualizar o crear el contacto en db // $rebillCustomerData = $request->only(['email', 'phone', 'personalId', 'address', 'fullName', 'zip']);
            // mandame la informacion de datos personalescuando crees la susbscripcion. No se puede actualizar datos

            //obtener datos personales
            $ptpTransaction = PlaceToPayTransaction::where('requestId', $request['requestId'])->first();
            $objetoStdClass = $this->placeToPayService->getByRequestId($request['requestId']);
            // $objetoStdClass = $placeToPayService->getByRequestId(677217);
            // Convertir el objeto stdClass en un objeto PHP
            $transactionByRequestId = json_decode(json_encode($objetoStdClass), false);

            //paymentLink data
            $paymentLinkData = $request->only(['gateway', 'type', 'contract_entity_id', 'contract_so', 'status', 'quotes', 'country']);
            $paymentLinkData['transactionId'] = $ptpTransaction->id;

            $paymentLinks = PlaceToPayPaymentLink::where([
                ["contract_so", $paymentLinkData["contract_so"]],
                ["status", "!=", "Contrato Efectivo"]
            ])->get();

            if ($paymentLinks->count() > 0) {
                $paymentLinks->first()->update($paymentLinkData);
                $paymentLink = PlaceToPayPaymentLink::where(
                    "contract_so",
                    $paymentLinkData["contract_so"]
                )->get()->first();
            } else {
                $paymentLink = PlaceToPayPaymentLink::create($paymentLinkData);
            }

            return response()->json([
                "transactionByRequestId" => $transactionByRequestId,
                "payment" => $paymentLink,
                "processURL" => $ptpTransaction->processUrl,
                "type" => "paymentLink"
            ]);
        } catch (\Exception $e) {
            Manage::error($e);
        }
    }

    public function getPaymentLink(Request $request, $saleId)
    {
        try {
            $paymentLinkPTP = PlaceToPayPaymentLink::where('contract_entity_id', $saleId)->first();
            $objetoStdClass = $this->placeToPayService->getByRequestId($paymentLinkPTP->transaction->requestId);
            // $objetoStdClass = $placeToPayService->getByRequestId(677217);
            // Convertir el objeto stdClass en un objeto PHP
            $transaction = json_decode(json_encode($objetoStdClass), false);
            return response()->json(["payer" => $transaction->request->payer, "checkout" => $paymentLinkPTP]);
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