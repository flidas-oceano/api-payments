<?php

namespace App\Http\Controllers;

use App\Models\FlowSPP;
use Illuminate\Http\Request;

class FlowSPPController extends Controller
{
    public function create(Request $request)
    {
        $dataFLowSPP = FlowSPP::getFlowSPPFromRequest($request);
        $flow_spp = FlowSPP::create($dataFLowSPP);
        return response()->json($flow_spp);
    }
    public function getById($id)
    {
        $flow_spp = FlowSPP::find($id);
        return response()->json($flow_spp);

    }
    public function getByContractId($contractId)
    {
        $flow_spp = FlowSPP::find($contractId);
        return response()->json($flow_spp);
    }
    public function updateOrCreate(Request $request){
        $dataFLowSPP = FlowSPP::getFlowSPPFromRequest($request);
        $updatedOrCreated = FlowSPP::updateOrCreate(['id' => $dataFLowSPP['id']],$dataFLowSPP);
        return response()->json($updatedOrCreated);
    }
}


