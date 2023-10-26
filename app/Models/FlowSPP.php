<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use stdClass;

class FlowSPP extends Model
{
    use HasFactory;
    protected $table = 'flows_spp';
    public $timestamps = true;
    protected $primaryKey = 'id';

    public $fillable = [
        'id',
        'contract_id',
        'contract_so',
        'reference',
        'zohoData',
    ];
    private static $formAttributes = [
        'id',
        'contract_id',
        'contract_so',
        'reference',
        'zohoData',
    ];

    public function transactions()
    {
        return $this->hasMany(PlaceToPayTransaction::class, 'flow_spp_id');
    }

    public static function getFlowSPPFromRequest($request)
    {
        return [
            'id' => $request->id,
            'contract_id' => $request->contract_id,
            'contract_so' => $request->contract_so,
            'reference' => $request->reference,
            'zohoData' => $request->zohoData,
        ];
    }


}


