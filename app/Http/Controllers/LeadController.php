<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Models\{Lead, PurchaseProgress};
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function getLeads()
    {
        $lead = Lead::all();
        return json_encode(['lead'=> $lead]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreLeadRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreLeadRequest $request)
    {
        $newOrUpdatedLead = Lead::updateOrCreate(['email' => $request->email], $request->all());

        return response()->json($newOrUpdatedLead);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Lead  $lead
     * @return \Illuminate\Http\Response
     */
    public function show(Lead $lead)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Lead  $lead
     * @return \Illuminate\Http\Response
     */
    public function edit(Lead $lead)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateLeadRequest  $request
     * @param  \App\Models\Lead  $lead
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateLeadRequest $request, Lead $lead)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Lead  $lead
     * @return \Illuminate\Http\Response
     */
    public function destroy(Lead $lead)
    {
        //
    }

    public function storeProgress(Request $request, $idPurchaseProgress)
    {
        $leadAttributes = $request->except(['step_number','savingProgress']);
        $newOrUpdatedLead = Lead::updateOrCreate(['email' => $request->email], $leadAttributes);
        $progress = PurchaseProgress::updateProgress($idPurchaseProgress, ['step_number' => $request->step_number, 'lead_id' => $newOrUpdatedLead->id]);
       
        return response()->json(['lead' => $newOrUpdatedLead, 'progress' => $progress]);
    }
}
