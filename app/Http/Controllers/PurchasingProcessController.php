<?php

namespace App\Http\Controllers;

use App\Models\{Lead, Contact, Address, Contract, PurchaseProgress, Product};
use App\Http\Requests\StorePurchasingProcessRequest;
use App\Http\Requests\UpdatePurchasingProcessRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Http\Requests\{UpdateLeadRequest, StoreContactRequest};

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Helper\ProgressBar;
use Illuminate\Support\Facades\Log;

class PurchasingProcessController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $allProcess = PurchaseProgress::where('user_id', $request->user)->get();
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
        $data = $request->only(['step_number', 'country', 'user_id']);
        $newProgress = PurchaseProgress::create($data);
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
        $progress = PurchaseProgress::getModel($id);
        if (empty($progress)) {
            return response()->json(['message' => 'El PurchaseProgress con id ' . $id . ' no existe'], 404);
        }

        $appEnv = [
            "progress" => $progress,
            "lead" => $progress->lead,
            "contact" => $progress->contact,
            "contract" => $progress->contract,
            "products" => isset($progress->contract->products) ? $progress->contract->products : null
        ];

        return response()->json($appEnv);
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
    public function update(Request $request, $id)
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

    public function stepCreateLead(UpdateLeadRequest $request)
    {
        /* Datos de prueba al postman
        {
        area_of_work: "",
        country: "",
        date_of_birth: "",
        dni: "",
        idPurchaseProgress: "39",
        locality: "",
        method_contact: "",
        name: "",
        postal_code: "",
        profession: "",
        province_state: "",
        registration_number: "",
        sex: "",
        speciality: "",
        step_number: 3,
        street: "",
        telephone: "",
        training_interest: "",
        username: ""
        }
        */

        $params = $request->only(['idPurchaseProgress', 'step_number']);
        $leadAttributes = $request->only(Lead::getFormAttributes());

        $newOrUpdatedLead = Lead::updateOrCreate([
            'email' => $leadAttributes['email']
        ], $leadAttributes);

        $purchaseProcess = PurchaseProgress::where('id', $params['idPurchaseProgress'])->first();
        $purchaseProcess->update(['lead_id' => $newOrUpdatedLead->id, 'step_number' => $params['step_number']]);

        Log::info("PurchasingProcessController-stepCreateLead-newOrUpdatedLead: " . print_r($newOrUpdatedLead, true));

        return response()->json([
            'newOrUpdatedLead' => $newOrUpdatedLead,
            'lead_id' => $newOrUpdatedLead->id,
            'progress' => $purchaseProcess
        ]);
    }

    public function stepConversionContact(StoreContactRequest $request)
    {
        $contactAttrs = $request->only(Contact::getFormAttributes());

        $newOrUpdatedContact = Contact::updateOrCreate([
            'dni' => $contactAttrs['dni']
        ], $contactAttrs);

        $progress = PurchaseProgress::updateProgress(
            $request->idPurchaseProgress,
            [
                'step_number' => $request->step_number,
                'contact_id' => $newOrUpdatedContact->id
            ]
        );

        return response()->json([
            'message' => 'success',
            'contact' => $newOrUpdatedContact,
            'contact_id' => $newOrUpdatedContact->id,
            'progress' => $progress,
            'lead' => $progress->lead
        ]);
    }

    private function getCurrencyByCountry($country)
    {
        switch ($country) {
            case 'Chile':
                return 'CLP';
            case 'México':
                return 'MXN';
            case 'Argentina':
                return 'ARG';
            default:
                return 'USD';
        }
    }

    public function stepConversionContract(Request $request)
    {
        $progress = PurchaseProgress::updateProgress(
            $request->idPurchaseProgress,
            ['step_number' => $request->step_number]
        );

        $currency = $this->getCurrencyByCountry($progress->country);

        $contractAttributes = [
            'name' => $progress->lead->name,
            'address' => $progress->contact->street,
            'country' => $progress->country,
            'currency' => $currency
        ];

        $contractId = null;

        if (is_null($progress->contract)) {
            $newContract = Contract::create($contractAttributes);
            $progress->update(['contract_id' => $newContract->id]);
            $contractId = $newContract->id;
        } else {
            $progress->contract->update($contractAttributes);
            $contractId = $progress->contract->id;
        }


        $products = collect($request->products)->map(function ($item) use ($contractId) {
            $is_gift = isset($item['gift']) ? $item['gift']:false;
            return [
                "quantity" => $item['quantity'],
                "product_code" => $item['product_code'],
                "price" => $item['price'],
                "discount" => $item['discount'],
                "title" => $item['title'],
                "contract_id" => $contractId,
                "gift" => $is_gift,
            ];
        })->all();

        // Actualiza los productos
        foreach ($products as $product) {
            Product::updateOrCreate([
                'contract_id' => $product['contract_id'],
                'product_code' => $product['product_code']
            ], $product);
        }

        return response()->json([
            "message" => "success",
            "progress" => $progress,
            "contract" => $progress->contract,
            "contact" => $progress->contact,
            "lead" => $progress->lead,
            "products" => $products
        ]);
    }



    public function updateEntityIdLeadVentas(Request $request)
    {
        $attrLead = $request->all();

        $newOrUpdatedLead = Lead::updateOrCreate([
            'email' => $attrLead["email"]
        ], $attrLead);

        return response()->json(['lead' => $newOrUpdatedLead]);
    }
}
