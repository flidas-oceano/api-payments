<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Models\{Lead,Contact,Address, PurchaseProgress};
use App\Http\Requests\StorePurchasingProcessRequest;
use App\Http\Requests\UpdatePurchasingProcessRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateLeadRequest;

use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Helper\ProgressBar;

class PurchasingProcessController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

      /*   $procceses = DB::table('purchasing_processes as pp')
                            ->join('leads as l','l.id','=','pp.lead_id_fk')
                            ->select('pp.*','l.*')
                            ->get(); */

        // $procceses = PurchasingProcess::with(['leads'])->get();
        // $procceses = Lead::with('purchasingProcesses')->get();
        $allProcess = PurchaseProgress::all();
        return response()->json($allProcess);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StorePurchasingProcessRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StorePurchasingProcessRequest $request)
    {
        $newProgress = PurchaseProgress::create($request->only(['step_number','country']));
        return response()->json($newProgress);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PurchasingProcess  $purchasingProcess
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $purchasingProcess = PurchaseProgress::getModel($id);
        if(empty($purchasingProcess)){
            return response()->json(['message' => 'El PurchaseProgress con id '.$id.' no existe'], 404);
        }

        return response()->json($purchasingProcess);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\PurchaseProgress  $purchasingProcess
     * @return \Illuminate\Http\Response
     */
    public function edit(PurchaseProgress $purchasingProcess)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatePurchasingProcessRequest  $request
     * @param  \App\Models\PurchaseProgress  $purchasingProcess
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,$id)
    {
        return PurchaseProgress::updateProgress($id, $request);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PurchaseProgress  $purchasingProcess
     * @return \Illuminate\Http\Response
     */
    public function destroy(PurchaseProgress $purchasingProcess)
    {
        //
    }
    // step1: pais
    public function stepCreateLead(UpdateLeadRequest $request){
        /* Datos de prueba al postman
            {
                "lead": {
                    "lead_id":null,
                    "entity_id_crm":"",
                    "lead_status":"",
                    "source_lead":"",
                    "lead_source":"",
                    "name":"",
                    "username":"",
                    "email":"",
                    "profession":"",
                    "speciality":"",
                    "method_contact_id_fk":"",
                    "telephone":"",
                    "contact_id_fk":""
                }
            }
        */
        $idPurchaseProgress = $request->only('idPurchaseProgress');
        $stepNumber = $request->only('step_number');

        $leadAttributes = $request->except(['country','idPurchaseProgress','step_number']);
        $newOrUpdatedLead = Lead::updateOrCreate([
            'email' => $leadAttributes['email']
        ], $request->all());

        //  $progress = PurchaseProgress::updateProgress(
        //     $idPurchaseProgress,
        //      ['step_number' => $request->step_number, 'lead_id' => $newOrUpdatedLead->id]
        //     );
        $purchaseProcess = PurchaseProgress::find($idPurchaseProgress);
        $purchaseProcess->lead_id = $newOrUpdatedLead->id;
        $purchaseProcess->step_number = $request->step_number;
        $purchaseProcess->save();

        return response()->json([
            'message' => 'success',
            'newOrUpdatedLead' => $newOrUpdatedLead,
            'lead_id' => $newOrUpdatedLead->id
        ]);
    }
    public function stepConversionContact(StoreContactRequest $request){
        /* Datos de prueba al postman
        {
            "contact": {
                "contact_id": null,
                "entity_id_crm": "aa",
                "dni": "aa",
                "sex": "aa",
                "date_of_birth": "aa",
                "addresses_id_fk": "aa",
                "registration_number": "aa",
                "area_of_work": "aa",
                "training_interest": "aa"
            },
            "address": {
                "address_id" : null,
                "country" : "conversionContact",
                "province_state" : "conversionContact",
                "postal_code" : "conversionContact",
                "street" : "conversionContact",
                "locality" : "conversionContact"
            }
        }
        */

        $idPurchaseProgress = $request->only('idPurchaseProgress');

        $contactAttrs = $request->except(['idPurchaseProgress','id']);
        $newOrUpdatedContact = Contact::updateOrCreate([
            'dni' => $contactAttrs['dni']
        ], $contactAttrs);

        // $progress = PurchaseProgress::updateProgress(
        //     $idPurchaseProgress,
        //      ['step_number' => $request->step_number,
        //       'lead_id' => $newOrUpdatedLead->id]
        //     );

        return response()->json([
            'message' => 'success',
            'newOrUpdatedContact' => $newOrUpdatedContact,
            'id' => $newOrUpdatedContact->id
        ]);
    }                                                                           
   
    public function updateEntityIdLeadVentas(Request $request){
        $attrLead = $request->all();
        $newOrUpdatedLead = Lead::updateOrCreate([
            'email' => $attrLead["email"]
            ], $attrLead);
       
        return response()->json(['lead' => $newOrUpdatedLead]);
    }
}