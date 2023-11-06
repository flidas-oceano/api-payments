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
        'date_to_pay',
        'failed_payment_attempts',
        'transactionId',
        'last_sent_status'
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
        // Obtener la fecha actual de date_to_pay y sumar un día
        $newDateToPay = date('Y-m-d 00:00:00', strtotime($sub->date_to_pay . ' +1 day'));
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
            'date_to_pay' => $newDateToPay
        ]);
        return $sub;
    }

    public static function generatePayerPayment($requestSubscriptionById)
    {
        return [
            "name" => $requestSubscriptionById['request']['payer']['name'],
            "surname" => $requestSubscriptionById['request']['payer']['surname'],
            "email" => $requestSubscriptionById['request']['payer']['email'],
            "document" => $requestSubscriptionById['request']['payer']['document'],
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

    public static function generatePayerPaymentSession($request)
    {
        return [
            "name" => $request['payer']['name'],
            "surname" => $request['payer']['surname'],
            "email" => $request['payer']['email'],
            "document" => $request['payer']['document'],
            "documentType" => $request['payer']['documentType'],
            "mobile" => $request['payer']['mobile'],
            "address" => [
                //domicilio
                "country" => $request['country'],
                //     // "state" => $request['state'],
                //     // "city" => $request['city'],
                //     // "postalCode" => $request['postalCode'],
                "street" => $request['payer']['address']['street'],
                //     // "phone" => $request['phone'],//+573214445566
            ]
        ];
    }

    public static function generateDetailPaymentSession($reference)
    {
        return [
            "reference" => $reference,
            "description" => "Prueba suscripcion contrato de MSK"
        ];
    }

    public static function generatePaymentDataSession($auth, $payer, $subscription, $expiration, $request)
    {
        return [
            "auth" => $auth,
            "locale" => "es_CO",
            "buyer" => $payer,
            "subscription" => $subscription,
            "expiration" => $expiration,
            "returnUrl" => "https://msklatam.com/ec/gracias",
            "ipAddress" => $request->ip(),
            "userAgent" => $request->header('User-Agent'),
            "skipResult" => true
        ];
    }

    public static function generatePayment($reference, $subscriptionToPay)
    {
        return [
            "reference" => $reference,
            "description" => "Pago de cuota " . $reference . " - " . $subscriptionToPay->currency . " " . $subscriptionToPay->total,
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
            "returnUrl" => "https://msklatam.com/ec/gracias",
        ];
    }

    public static function createWith($request, $response)
    {
        return self::create([
            'transactionId' => $request->id,
            'nro_quote' => $request->nro_quote,
            'date' => $response['payment'][0]['status']['date'],
            'requestId' => $response['requestId'],
            'total' => $response['request']['payment']['amount']['total'],
            'currency' => $response['request']['payment']['amount']['currency'],
            'status' => $response['payment'][0]['status']['status'],
            'date_to_pay' => $response['status']['date'],
            'reason' => $response['payment'][0]['status']['reason'],
            'message' => $response['payment'][0]['status']['message'],
            'authorization' => $response['payment'][0]['authorization'] ?? null,
            'reference' => $response['payment'][0]['reference'] ?? null,
            // 'type' => , //TODO: me parece que es mejor borrarlo de la tabla. O usarla para diferenciar: subscription, advancedInstallment
            // 'expiration_date' => , //TODO: definir cuando se espera que expire una cuota.
        ]);
    }



    public static function suspend($subscription)
    {
        $subscription->update(['status' => 'SUSPEND']);
    }

    public function isPending($transaction, $subscriptionByRequestId)
    {
        if ($this->status === 'PENDING') {
            return $subscriptionByRequestId['payment'][0]['status']['status'] ?? $subscriptionByRequestId['status']['status'];
        } else {
            return $this->status;
        }
    }

    public function isApprovedPayment($transaction, $subscriptionByRequestId){
            // Actualizo el transactions, campo: installments_paid
            $transaction->update(['installments_paid' => $transaction->installments_paid + 1]);

            if ($transaction->paymentLinks()->first() !== null) {
                $transaction->paymentLinks()->first()->update(['status' => 'Contrato Efectivo']);
            }

            // Actualiza cuota
            $updatePayment = PlaceToPaySubscription::updateWith($transaction, $subscriptionByRequestId, $this->id);
           // $requestSubscriptionById = $this->getByRequestId($transaction['requestId'], false, true);

            // creas todas las cuotas restantes, si hay
            $result = [
                "newPayment" => $updatePayment,
                "transaction" => $transaction,
                //"response" => $requestSubscriptionById,
                // "data" => $data,
            ];

            return $result;
    }
    public static function updateWith($request, $data, $quoteId)
    {
        $payment = self::find($quoteId);

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

    public function updateSentStatus()
    {
        self::update(['last_sent_status' => $this->status]);
    }
}
