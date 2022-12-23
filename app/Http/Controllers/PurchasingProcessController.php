<?php

namespace App\Http\Controllers;

use App\Models\{PurchasingProcess,Lead};
use App\Http\Requests\StorePurchasingProcessRequest;
use App\Http\Requests\UpdatePurchasingProcessRequest;
use Illuminate\Support\Facades\DB;


class PurchasingProcessController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // $procceses = DB::table('purchasing_proccess')->get();

        $procceses = DB::table('purchasing_processes as pp')
                            ->join('leads as l','l.id','=','pp.lead_id_fk')
                            ->select('pp.*','l.*')
                            ->get();

        // $procceses = PurchasingProcess::with(['leads'])->get();
        // $procceses = Lead::with('purchasingProcesses')->get();

        return $procceses;
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
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\PurchasingProcess  $purchasingProcess
     * @return \Illuminate\Http\Response
     */
    public function show(PurchasingProcess $purchasingProcess)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\PurchasingProcess  $purchasingProcess
     * @return \Illuminate\Http\Response
     */
    public function edit(PurchasingProcess $purchasingProcess)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdatePurchasingProcessRequest  $request
     * @param  \App\Models\PurchasingProcess  $purchasingProcess
     * @return \Illuminate\Http\Response
     */
    public function update(UpdatePurchasingProcessRequest $request, PurchasingProcess $purchasingProcess)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\PurchasingProcess  $purchasingProcess
     * @return \Illuminate\Http\Response
     */
    public function destroy(PurchasingProcess $purchasingProcess)
    {
        //
    }
}
