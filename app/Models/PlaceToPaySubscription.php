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
        $subscription = self::find($subscriptionId);
        $subscription->update(['failed_payment_attempts' => $subscription->failed_payment_attempts + 1]);
        $subscription->save();

        return $subscription->failed_payment_attempts;
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
    public static function updateSubscription($id, $response, $payment)
    {
        $sub = self::find($id);
        $sub->update([
            'status' => $response['payment'][0]['status']['status'],
            'message' => $response['payment'][0]['status']['message'],
            'reason' => $response['payment'][0]['status']['reason'],
            'date' => $response['payment'][0]['status']['date'],
            'reference' => $payment['reference'] ?? null,
            'authorization' => $response['payment'][0]['authorization'] ?? null,
            'requestId' => $response['requestId'],
            'currency' => $payment['amount']['currency'],
            'total' => $payment['amount']['total'],
        ]);
        return $sub;
    }

    public static function generatePayerPayment($requestSubscriptionById)
    {
        return [
            "name" => $requestSubscriptionById['request']['payer']['name'],
            //$request->lead->name
            "surname" => $requestSubscriptionById['request']['payer']['surname'],
            //$request->lead->username
            "email" => $requestSubscriptionById['request']['payer']['email'],
            //$request->lead->email
            "document" => $requestSubscriptionById['request']['payer']['document'],
            //contact->dni,rut,rfc,mui
            "documentType" => $requestSubscriptionById['request']['payer']['documentType'],
            "mobile" => $requestSubscriptionById['request']['payer']['mobile'],
            // "address" => [ //domicilio
            //     // "country" => $request['country'],
            //     // "state" => $request['state'],
            //     // "city" => $request['city'],
            //     // "postalCode" => $request['postalCode'],
            //     "street" => $requestSubscriptionById['request']['payer']['address']['street'],
            //     // "phone" => $request['phone'],//+573214445566
            // ]
        ];
    }

    public static function generatePayment($reference, $subscriptionToPay)
    {
        return [
            "reference" => $reference,
            "description" => "",
            "amount" => [
                "currency" => $subscriptionToPay->currency,
                "total" => $subscriptionToPay->total
            ]
        ];
    }

    public static function generateDataPayment($auth, $paymentData, $payment, $token_collect, $expiration)
    {
        return [
            "auth" => $auth,
            "locale" => "es_CO",
            "payer" => $paymentData,
            "payment" => $payment,
            "instrument" => [
                "token" => [
                    "token" => $token_collect
                ]
            ],
            "expiration" => $expiration,
            "returnUrl" => "https://dnetix.co/p2p/client",
        ];
    }

    public static function createWith($request, $response)
    {
        return self::create([
            'transactionId' => $request->id,
            'nro_quote' => $request->nro_quote,
            'date' => $response['status']['date'],
            'requestId' => $response['requestId'],
            'total' => $response['request']['payment']['amount']['total'],
            'currency' => $response['request']['payment']['amount']['currency'],
            'status' => $response['status']['status'],
            'date_to_pay' => $response['status']['date'],
            'reason' => $response['status']['reason'],
            'message' => $response['status']['message'],
            'authorization' => $response['payment'][0]['authorization'] ?? null,
            'reference' => $response['payment'][0]['reference'] ?? null,
            // 'type' => , //TODO: me parece que es mejor borrarlo de la tabla. O usarla para diferenciar: subscription, advancedInstallment
            // 'expiration_date' => , //TODO: definir cuando se espera que expire una cuota.
        ]);
    }

    public static function updateWith($request, $data)
    {
        $payment = self::find($request->id);

        $payment->update([
            'transactionId' => $request->id,
            'date' => $data['status']['date'],
            'status' => $data['status']['status'],
            'reason' => $data['status']['reason'],
            'message' => $data['status']['message'],
            'authorization' => $data['payment'][0]['authorization'] ?? null,
            'reference' => $data['payment'][0]['reference'] ?? null,
            // 'type' => , //TODO: me parece que es mejor borrarlo de la tabla. O usarla para diferenciar: subscription, advancedInstallment
            // 'expiration_date' => , //TODO: definir cuando se espera que expire una cuota.
        ]);

        return $payment;
    }

    public static function suspend($subscription)
    {
        $subscription->update(['status' => 'SUSPEND']);
    }
}