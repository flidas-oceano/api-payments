<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class PurchaseProgress extends Model
{
    use HasFactory;

    protected $table = 'purchase_progress';
    //protected $fillable = ['lead_id_fk'];
    protected $hidden = ['created_at','updated_at'];

    protected $guarded = [];
    public function leads(){
        $leads = $this->hasOne(Lead::class,'id','lead_id_fk');
        return $leads;
    }

    public static function getModel($purchaseProgressId){
        return PurchaseProgress::where('id', $purchaseProgressId)->first();
    }

    public static function updateProgress($purchaseProgressId, $values){
        PurchaseProgress::findOrFail($purchaseProgressId)->update($values);
        return PurchaseProgress::find($purchaseProgressId);
    }
}
