<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\Lead;
use App\Models\PurchaseProgress;
use Illuminate\Http\Request;

class ContactController extends Controller
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
     * @param  \App\Http\Requests\StoreContactRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreContactRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Contact  $contact
     * @return \Illuminate\Http\Response
     */
    public function show(Contact $contact)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Contact  $contact
     * @return \Illuminate\Http\Response
     */
    public function edit(Contact $contact)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateContactRequest  $request
     * @param  \App\Models\Contact  $contact
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateContactRequest $request, Contact $contact)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Contact  $contact
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contact $contact)
    {
        //
    }

    public function storeProgress(Request $request, $idPurchaseProgress)
    {
        $progress = PurchaseProgress::updateProgress($idPurchaseProgress, ['step_number' => $request->step_number]);
        $contactAttributes = $request->only(Contact::getFormAttributes());

        if(is_null($progress->contact)){
            $newContact = Contact::create($contactAttributes);
            $progress->lead->update(['contact_id' => $newContact->id]);
            $progress->update(['contact_id' => $newContact->id]);
        }else{
            $progress->contact->update($contactAttributes);
        }

        return response()->json(['contact' => $progress->contact ,'lead' => $progress->lead , 'progress' => $progress]);
    }

    public function updateEntityIdContactSales(Request $request){
        $attrContact = $request->only(Contact::getFormAttributes());

        $newOrUpdatedContact = Contact::updateOrCreate([
            'dni' => $attrContact["dni"]
        ], $attrContact);
        
        //Limpiar el entity id porque en zoho se borra el lead.
        $idProggress = $request->progress['id'];
        $progress = PurchaseProgress::where("id", $idProggress)->first();
        Lead::updateOrCreate(['id' => $progress->lead->id], ['entity_id_crm' => null]);

        return response()->json(['contact' => $newOrUpdatedContact]);
    }


}
