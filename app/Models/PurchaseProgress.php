<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class PurchaseProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'step_number',
        'country',
        'title',
        'lead_id',
        'contact_id',
        'contract_id'
    ];
    protected $table = 'purchase_progress';
    protected $hidden = ['created_at','updated_at'];
    protected $guarded = [];

    public function lead(){
        $lead = $this->hasOne(Lead::class,'id','lead_id');
        return $lead;
    }

    public function contact(){
        $contact = $this->hasOne(Contact::class,'id','contact_id');
        return $contact;
    }

    public static function getModel($purchaseProgressId){
        $progress = PurchaseProgress::where('id', $purchaseProgressId)->first();
        /*  if(!is_null($progress->lead)){
            $progress->contact = $progress->lead->hasOne(Contact::class,'id','contact_id');
        } */

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
