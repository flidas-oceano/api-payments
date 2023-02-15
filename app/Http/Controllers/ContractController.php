<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Contract;
use App\Models\Product;
use App\Models\PurchaseProgress;
use Illuminate\Http\Request;

class ContractController extends Controller
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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $contract = Contract::where('id',$id)->first();
        $product = $contract->products;
        return response()->json([
            'message'=> 'success',
            'contract' => $contract
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function storeProgress(Request $request, $idPurchaseProgress)
    {

        $progress = PurchaseProgress::updateProgress($idPurchaseProgress, ['step_number' => $request->step_number]);

        $contractAttributes = [
        'name' => $progress->lead->name,
        'address' => $progress->contact->street,
        'country' => $progress->country,
        'currency' => ""
        ];

        if(is_null($progress->contract)){
            $newContract = Contract::create($contractAttributes);
            $progress->update(['contract_id' => $newContract->id]);

        }else{
            $progress->contract->update($contractAttributes);
        }

        $products = collect($request->products)->map(function($item) use ($progress){
            $price = 0;
            $productCode = 0;
            if(isset($item['precio'])){
                $price = $item['precio'];
                $productCode = $item['id'];

            }else{
                $price = $item['price'];
                $productCode = $item['product_code'];

            }
            return [
                "quantity" => $item['quantity'],
                "product_code" => $productCode,
                "price" => $price,
                "discount" => $item['discount'],
                "title" => $item['title'],
                "contract_id" => $progress->contract->id,
            ];
        })->all();

        foreach($products as $product){
            Product::updateOrCreate([
                'contract_id' => $product['contract_id'],
                'product_code' => $product['product_code']
            ], $product);
        }

        return response()->json(['products' => $products,'contract' => $progress->contract ,'contact' => $progress->contact ,'lead' => $progress->lead , 'progress' => $progress]);
    }
}
