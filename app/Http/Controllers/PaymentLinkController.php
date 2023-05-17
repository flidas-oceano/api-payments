<?php

namespace App\Http\Controllers;

use App\Models\PaymentLink;
use App\Models\RebillCustomer;
use Illuminate\Http\Request;

class PaymentLinkController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }


    public function create(Request $request)
    {
        $rebillCustomerData = $request->only(['email', 'phone', 'personalId', 'address', 'fullName', 'zip']);
        $paymentLinkData = $request->only(['gateway', 'type', 'contract_entity_id', 'contract_so', 'status', 'quotes', 'country']);

        //  dd($rebillCustomerData);

        $customer = RebillCustomer::updateOrCreate(["email" => $rebillCustomerData["email"]], $rebillCustomerData);
        $paymentLinkData['rebill_customer_id'] = $customer->id;
        //  dd($paymentLinkData);
        $paymentLink = PaymentLink::updateOrCreate(["so" => $paymentLinkData["contract_so"]], $paymentLinkData);

        return response()->json(["customer" => $customer, "payment" => $paymentLink]);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }


    public function show(Request $request, $saleId)
    {
        $paymentLink = PaymentLink::where('contract_entity_id', $saleId)->first();
        $customer = RebillCustomer::where('id', $paymentLink->rebill_customer_id)->first();

        return response()->json(["customer" => $customer->toArray(), "checkout" => $paymentLink->toArray()]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\PaymentLink  $paymentLink
     * @return \Illuminate\Http\Response
     */
    public function edit(PaymentLink $paymentLink)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\PaymentLink  $paymentLink
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, PaymentLink $paymentLink)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PaymentLink  $paymentLink
     * @return \Illuminate\Http\Response
     */
    public function destroy(PaymentLink $paymentLink)
    {
        //
    }
}