<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class PurchaseProgress extends Model
{
    use HasFactory;

    protected $table = 'purchase_progress';
    protected $hidden = ['created_at','updated_at'];

    protected $guarded = [];
    public function leads(){
        $leads = $this->hasOne(Lead::class,'id','lead_id');
        return $leads;
    }

    public static function getModel($purchaseProgressId){
        $progress = PurchaseProgress::where('id', $purchaseProgressId)->first();
        $progress->lead = $progress->hasOne(Lead::class,'id','lead_id')->first();
        
        return $progress;
    }

    public static function updateProgress($purchaseProgressId, $requestValues){
        $purchase = PurchaseProgress::findOrFail($purchaseProgressId);
        if(gettype($requestValues) === 'object'){
            $purchase->update($requestValues->only(['step_number', 'country']));
        }else{
            $purchase->update($requestValues);
        }
        return PurchaseProgress::find($purchaseProgressId);
    }
}
