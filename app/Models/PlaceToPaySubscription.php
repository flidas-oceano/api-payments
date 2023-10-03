<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PlaceToPaySubscription extends Model
{
    use HasFactory;
    protected $table = 'placetopay_subscriptions';
    public $timestamps = true;
    protected $primaryKey = 'id';

    public $fillable = [
        'id',
        'status',
        'reason',
        'message',
        'date',
        'requestId',
        'contact_id',
        'authorization',
        'total',
        'currency',
        'nro_quote',
        'reference',
        'type',
        'expiration_date',
        'transactionId',
        'date_to_pay',
        'failed_payment_attempts'
    ];
    private static $formAttributes = [
        'id',
        'requestIdFather',
        'status',
        'reason',
        'message',
        'date',
        'requestId',
        'contact_id',
        'authorization',
        'total',
        'currency',
        'reference',
        'type',
        'expiration_date',
        'transactionId',
        'date_to_pay',
        'failed_payment_attempts'
    ];
    public function transaction()
    {
        return $this->belongsTo(PlaceToPayTransaction::class, 'transactionId');
    }


    public static function incrementFailedPaymentAttempts($subscriptionId)
    {
        // self::where('id', $subscriptionId)->increment('failed_payment_attempts', 1);
        PlaceToPaySubscription::find($subscriptionId)->update(['failed_payment_attempts' => DB::raw('COALESCE(failed_payment_attempts, 0) + 1')]);
    }

    public static function duplicateAndReject($subscriptionId, $response, $payment)
    {
        // Duplica la cuota actual
        $originalSubscription = self::find($subscriptionId);
        $newSubscriptionRejected = $originalSubscription->replicate();
        $newSubscriptionRejected->save();

        // Actualiza la cuota original (marcándola como rechazada)
        self::updateSubscription($newSubscriptionRejected->id, $response, $payment);

        return $newSubscriptionRejected;
    }
    public static function updateSubscription($id,$response,$payment){
        PlaceToPaySubscription::find($id)->update([
            'status' =>         'REJECTED',
            // 'status' =>         $response['status']['status'],
            'message' =>        $response['status']['message'],
            'reason' =>         $response['status']['reason'],
            'date' =>           $response['status']['date'],
            'reference' =>      $response['payment'][0]['reference'] ?? null,
            'authorization' =>  $response['payment'][0]['authorization'] ?? null,
            'requestId' =>      $response['requestId'],
            'currency' =>       $payment['amount']['currency'],
            'total' =>          $payment['amount']['total'],
        ]);
    }
}
