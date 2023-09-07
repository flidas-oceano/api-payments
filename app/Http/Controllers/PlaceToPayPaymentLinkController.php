<?php

namespace App\Http\Controllers;

use App\Models\PaymentLink;
use App\Models\PlaceToPayPaymentLink;
use App\Models\RebillCustomer;
use App\Services\PlaceToPay\PlaceToPayService;
use Faker\Provider\ar_EG\Payment;
use Illuminate\Http\Request;

class PlaceToPayPaymentLinkController extends Controller
{
    protected $placeToPayService;

    public function __construct(PlaceToPayService $placeToPayService)
    {
        $this->placeToPayService = $placeToPayService;
    }

    public function create(Request $request)
    {
        $rebillCustomerData = $request->only(['email', 'phone', 'personalId', 'address', 'fullName', 'zip']);
        $paymentLinkData = $request->only(['gateway', 'type', 'contract_entity_id', 'contract_so', 'status', 'quotes', 'country']);


        $customer = RebillCustomer::updateOrCreate(["email" => $rebillCustomerData["email"]], $rebillCustomerData);
        $paymentLinkData['rebill_customer_id'] = $customer->id;

        $paymentLinks = PaymentLink::where([
            ["contract_so", $paymentLinkData["contract_so"]],
            ["status", "!=", "Contrato Efectivo"]
        ])->get();

        if($paymentLinks->count() > 0){
            $paymentLinks->first()->update($paymentLinkData);
            $paymentLink = PaymentLink::where(
                "contract_so" , $paymentLinkData["contract_so"]
            )->get()->first();
        }else{
            $paymentLink = PaymentLink::create($paymentLinkData);
        }

        return response()->json(["customer" => $customer, "payment" => $paymentLink, "type" => "paymentLink"]);
    }

    public function show(Request $request, $saleId)
    {
        $paymentLinkPTP = PlaceToPayPaymentLink::where('contract_entity_id', $saleId)->first();
        $objetoStdClass = $this->placeToPayService->getByRequestId($paymentLinkPTP->transaction->requesId);
        // $objetoStdClass = $placeToPayService->getByRequestId(677217);
        // Convertir el objeto stdClass en un objeto PHP
        $transaction = json_decode(json_encode($objetoStdClass), false);
        return response()->json(["customer" => $transaction->request->payer, "checkout" => $paymentLinkPTP]);
    }
}
