<?php

namespace App\Http\Controllers;

use App\Models\{Lead,Contact,Address, PurchaseProgress};
use App\Http\Requests\StorePurchasingProcessRequest;
use App\Http\Requests\UpdatePurchasingProcessRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateLeadRequest;

use Illuminate\Support\Facades\Validator;


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
        $leadAttributes = $request->except(['country']);
        $newOrUpdatedLead = Lead::updateOrCreate([
            'email' => $leadAttributes['email']
        ], $request->all());

        return response()->json([
            'message' => 'success',
            'newOrUpdatedLead' => $newOrUpdatedLead,
            'id' => $newOrUpdatedLead->id
        ]);
    }
    // step3: => nombre aconvertir a contacto :
    /** 
        Lead : dni x
        Lead : sexo x
        Contact: fechanacimiento x
        PurchasingProccess-Address : pais 
        Address : provincia/estado x
        Address : codpostal x
        Address : direccion x
        Address : localidad x
        Contact: nummatricula x
        Lead : areatrabajo x
        Contact: interesformacion x
    */
    public function stepConversionContact(Request $request){
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
        $dataJson = json_decode($request->input('dataJson'), true);
        

        $validator = Validator::make($dataJson,[
            // "contact.contact_id"=> "required",
            // "contact.entity_id_crm"=> "required",
            "contact.dni"=> "required",
            "contact.sex"=> "required",
            "contact.date_of_birth"=> "required",
            // "contact.addresses_id_fk"=> "required",
            // "contact.registration_number"=> "required",
            // "contact.area_of_work"=> "required",
            // "contact.training_interest"=> "required",

            // "address.address_id"=> "required",
            "address.country"=> "required",
            "address.province_state"=> "required",
            "address.postal_code"=> "required",
            "address.street"=> "required",
            "address.locality"=> "required",
            
        ],[
            "lead.name.required"=>"Name Should be filled",
            "lead.email.min"=>" Email length should be more than 8"
        ]);

        if($validator->fails()){
            return response()->json([
                'message' => 'fail',
                'validator' => $validator
            ]);
        }

        $dataJson = json_decode($request->input('dataJson'));

        // $address = $request->request->get('address');
        // $contact = $request->request->get('contact');

        $newAddress = new Address();
        /*region address */
            $newAddress->id = isset($dataJson->address->address_id) ? $dataJson->address->address_id:null;
            $newAddress->country = $dataJson->address->country;
            $newAddress->province_state = $dataJson->address->province_state;
            $newAddress->postal_code = $dataJson->address->postal_code;
            $newAddress->street = $dataJson->address->street;
            $newAddress->locality = $dataJson->address->locality;

            isset($dataJson->address->address_id) ? 
                $newAddress->update():
                $newAddress->save();
        /*end region address */

        $newContact = new Contact();
        /* region contacto */
            $newContact->id = isset($dataJson->contact->contact_id) ? $dataJson->contact->contact_id:null;

            // $newContact->entity_id_crm = isset($leadentity_id_crm);
            $newContact->dni = $dataJson->contact->dni;
            $newContact->sex = $dataJson->contact->sex;
            $newContact->date_of_birth = $dataJson->contact->date_of_birth;
            $newContact->addresses_id_fk =  isset($newAddress['id']) ? $newAddress['id']:null;
            $newContact->registration_number = $dataJson->contact->registration_number;
            $newContact->area_of_work = $dataJson->contact->area_of_work;
            $newContact->training_interest = $dataJson->contact->training_interest;
                
            isset($dataJson->contact->contact_id) ? 
                $newContact->update():
                $newContact->save();
        /*end region contacto */
        
        return response()->json([
            'message' => 'success',
            'newContact' => $newContact,
            'newAddress' => $newAddress,
            'validator' => $validator
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