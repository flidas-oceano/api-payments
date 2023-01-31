<?php

namespace App\Http\Controllers;

use App\Models\{Lead,Contact,Address, PurchaseProgress};
use App\Http\Requests\StorePurchasingProcessRequest;
use App\Http\Requests\UpdatePurchasingProcessRequest;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
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
public function stepCreateLead(Request $request){
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
    // return json_encode(['request' => $request->all()]);
    
    // dump($request->all());

    // $type = gettype($request->collect());
    // $objectRequest = $request->collect();
    // $arrayRequest = $request->all();

    // $jsondecode = json_decode($objectRequest); 
    // $jsonencode = json_encode($objectRequest); 

    // $typedato = gettype(json_decode($request->input('lead')));
    // $dato = json_decode($request->input('lead'), true);//array
    // $dato = json_decode($request, true);//stdclass
    // $typedato = gettype(json_decode($request->input('lead')));


    // $typedato2 = gettype($request->all());
    // $dato2 = $request->all();


    // return response()->json(['country' => $dato->country]);

    $dato = json_decode($request->input('dataJson'), true);

    $validator = Validator::make($dato,[
            "lead.name"=> "required",
            "lead.username"=> "required",
            "lead.email"=> "required|email|min:8|",
            "lead.profession"=> "required",
            "lead.speciality"=> "required",
            "lead.telephone"=> "required"
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
    
    $newLead = new Lead();

    /*region lead */
        $newLead->id =              isset($dataJson->lead->lead_id)? $dataJson->lead->lead_id:null;
        $newLead->entity_id_crm =   isset($dataJson->lead->entity_id_crm);
        $newLead->lead_status =     isset($dataJson->lead->lead_status);
        $newLead->source_lead =     isset($dataJson->lead->source_lead);
        $newLead->lead_source =     isset($dataJson->lead->lead_source);

        $newLead->name =        $dataJson->lead->name;
        $newLead->username =    $dataJson->lead->username;
        $newLead->email =       $dataJson->lead->email;
        $newLead->telephone =   $dataJson->lead->telephone;
        $newLead->profession =  $dataJson->lead->profession;
        $newLead->speciality =  $dataJson->lead->speciality;
        // if(!isset($lead['lead_id'])){
        //     $newLead->contact_id_fk = $newContact->id;
        // }
        isset($dataJson->lead->lead_id)? 
            $newLead->update():
            $newLead->save();
    /*region lead */

    return response()->json([
        'message' => 'success',
        'newLead' => $newLead,
        'validator' => $validator
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
    // step1: pais
public function stepCreateLeadOld(Request $request){
    /* Datos de prueba al postman
    {
        "contact": {
            "contact_id" : null,
            "username" : "cc",
            "email": "ccc"
        },
        "lead": {
            "lead_id" : null,
            "name" : "dd",
            "username" : "dd",
            "telephone" : "addda",
            "profession" : "dd",
            "specialty" : "dddd",
            "method_contact" : 0
        }
    }
    */
    // return json_encode(['request' => $request->all()]);
    
    // $validator = Validator::make($request->all(),[
        //     "lead.name"=>"required",
        //     "lead.email"=>"required|email|min:8|",
        //     "lead.userame"=>"required|email|min:8|",
        //     "lead.telephone"=>"required|email|min:8|",
        //     "lead.proffesion"=>"required|email|min:8|",
        //     "lead.speciality"=>"required|email|min:8|",
        // ],[
        //     "lead.name.required"=>"Name Should be filled",
        //     "lead.email.min"=>" Email length should be more than 8"
        // ]);

        // if($validator->fails()){
        //     return json_encode(
        //         ['message' => 'fail',
        //         'validator' => $validator]);
        // }else{
        //     return json_encode([
        //         'message' => 'success',
        //         'validator' => $validator]);

    // }
    $contact = $request->request->get('contact');
    $lead = $request->request->get('lead');

    $newContact = new Contact();
    /* region contacto */
    $newContact->id = isset($contact['contact_id']) ? $contact['contact_id']:null;
    $newContact->email = $contact['email'];
    $newContact->username = $contact['username'];
        
    isset($contact['contact_id']) ? 
        $newContact->update():
        $newContact->save();
    /*end region contacto */


    $newLead = new Lead();
    /*region lead */
    $newLead->id = isset($lead['lead_id'])? $lead['lead_id']:null;
    $newLead->entity_id_crm = isset($lead['entity_id_crm']);
    $newLead->name = $lead['name'];
    $newLead->username = $lead['username'];
    $newLead->telephone = $lead['telephone'];
    $newLead->profession = $lead['profession'];
    $newLead->specialty = $lead['specialty'];
    if(!isset($lead['lead_id'])){
        $newLead->contact_id_fk = $newContact->id;
        // $dbLead = Lead::find($lead['lead_id']);
        // if(!isset($dbLead->contact_id_fk)){
        //     $newLead->contact_id_fk = $newContact->id;
        // }
    }
    isset($lead['lead_id'])? 
        $newLead->update():
        $newLead->save();

    /*region lead */
    return json_encode(['newContact' => $newContact,'newLead' => $newLead]);

    /*
        // $newAdress = new Address();
        // $newAdress->type_of_address = $request->request->get('type_of_address');
        // $newAdress->country = $request->request->get('country');
        // $newAdress->postal_code = $request->request->get('postal_code');
        // $newAdress->street = $request->request->get('street');
        // $newAdress->locality = $request->request->get('locality');
        // $newAdress->province_state = $request->request->get('province_state');
        // $newAdress->save();
        
        // $newContact = new Contact();
        // $newContact->entity_id_crm = $request->request->get('entity_id_crm');
        // $newContact->username = $request->request->get('username');
        // $newContact->date_of_birth = $request->request->get('date_of_birth');
        // $newContact->registration_number = $request->request->get('registration_number');
        // $newContact->training_interest = $request->request->get('training_interest');
        // $newContact->email = $request->request->get('email');
        // $newContact->save();

        // $newLead = new Lead;
        // // $newLead->entity_id_crm = $request->request->get('entity_id_crm');
        // $newLead->name = $request->request->get('name');
        // $newLead->username = $request->request->get('username');
        // $newLead->telephone = $request->request->get('telephone');
        // $newLead->lead_status = $request->request->get('lead_status');
        // $newLead->source_lead = $request->request->get('source_lead');
        // $newLead->lead_source = $request->request->get('lead_source');
        // $newLead->method_contact_id_fk = $request->request->get('method_contact_id_fk');
        // $newLead->contact_id_fk = $newContact->id;
        // $newLead->addresses_id_fk = $newAdress->id;
        // $newLead->lead_source = $request->request->get('lead_source');
        // $newLead->area_of_work = $request->request->get('area_of_work');
        // $newLead->profession = $request->request->get('profession');
        // $newLead->specialty = $request->request->get('specialty');
        // $newLead->dni = $request->request->get('dni');
        // $newLead->sex = $request->request->get('sex');

        // $newLead->save();
    */
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
    public function stepCreateContactOld(Request $request){
          /* Datos de prueba al postman
        {
            "contact": {
                "contact_id" : null,
                "date_of_birth" : "conversionContact",
                "registration_number": "conversionContact",
                "training_interest": "conversionContact"
            },
            "address": {
                "address_id" : null,
                "country" : "conversionContact",
                "province_state" : "conversionContact",
                "postal_code" : "conversionContact",
                "street" : "conversionContact",
                "locality" : "conversionContact"
            },
            "lead": {
                "lead_id" : null,
                "dni" : "conversionContact",
                "sex" : "conversionContact",
                "area_of_work" : "conversionContact",
            }
        }
        */
        $contact = $request->request->get('contact');
        $lead = $request->request->get('lead');
        $address = $request->request->get('address');

        $newContact = new Contact();
        /* region contacto */
            $newContact->id = isset($contact['contact_id']) ? $contact['contact_id']:null;
            $newContact->date_of_birth = $contact['date_of_birth'];
            $newContact->registration_number = $contact['registration_number'];
            $newContact->training_interest = $contact['training_interest'];
                
            isset($contact['contact_id']) ? 
                $newContact->update():
                $newContact->save();
        /*end region contacto */

        $newAddress = new Address();
        /*region address */
            $newAddress->id = isset($address['address_id']) ? $address['address_id']:null;
            $newAddress->country = $address['country'];
            $newAddress->province_state = $address['province_state'];
            $newAddress->postal_code = $address['postal_code'];
            $newAddress->street = $address['street'];
            $newAddress->locality = $address['locality'];

            isset($address['address_id']) ? 
                $newAddress->update():
                $newAddress->save();
        /*end region address */
        
        $newLead = new Lead();
        /*region lead */
            $newLead->id = isset($lead['lead_id'])? $lead['lead_id']:null;
            $newLead->entity_id_crm = isset($lead['entity_id_crm']);
            $newLead->dni = $lead['dni'];
            $newLead->sex = $lead['sex'];
            $newLead->area_of_work = $lead['area_of_work'];
            if(!isset($lead['lead_id'])){
                $newLead->contact_id_fk = $newContact->id;
                // $dbLead = Lead::find($lead['lead_id']);
                // if(!isset($dbLead->contact_id_fk)){
                //     $newLead->contact_id_fk = $newContact->id;
                // }
            }
            isset($lead['lead_id'])? 
                $newLead->update():
                $newLead->save();

        /*region lead */
        return json_encode(['newContact' => $newContact,'newAddress' => $newAddress,'newLead' => $newLead]);
    }
}