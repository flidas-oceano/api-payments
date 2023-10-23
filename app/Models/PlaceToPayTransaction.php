<?php

namespace App\Models;

use App\Services\PlaceToPay\PlaceToPayService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class PlaceToPayTransaction extends Model
{
    use HasFactory;

    protected $table = 'placetopay_transactions';
    public $timestamps = true;
    protected $primaryKey = 'id';

    public $fillable = [
        'id',
        'status',
        'reason',
        'message',
        'date',
        'requestId',
        'processUrl',
        'contact_id',
        'lead_id',
        'authorization',
        'total',
        'currency',
        'reference',
        'type',
        'token_collect_para_el_pago',
        'expiration_date',
        'remaining_installments',
        'first_installment',
        'quotes',
        'installments_paid',
        'paymentData',
        'transaction_id',
        'contract_id'

    ];
    private static $formAttributes = [
        'id',
        'requestId',
        'processUrl',
        'contact_id',
        'authorization',
        'total',
        'currency',
        'reference',
        'type',
        'token_collect_para_el_pago',
        'status',
        'reason',
        'message',
        'date',
        'expiration_date',
        'remaining_installments',
        'first_installment',
        'quotes',
        'installments_paid',
        'paymentData',
    ];

    private static $messageOfPtp = [
        'FAILED' => 'Hubo un error con el pago de la sesion, cree otra.',
        'APPROVED' => 'Ya se ha realizado el pago de la primera cuota.',
        'REJECTED' => 'La tarjeta fue rechazada, cree otra session e ingrese denuevo los datos de la tarjeta.',
        'PENDING' => 'El estado de la peticion de la tarjeta estan pendientes.',
        'DESCONOCIDO' => 'Se desconoce el error. Mire los logs o consulte en PTP.',
    ];

    function isSubscription()
    {
        return ($this->type === 'requestSubscription') ? true : false;
    }
    function isAdvancedSubscription()
    {
        return $this->first_installment !== null;
    }
    function installmentsToPay()
    {
        $diferencia = $this->quotes - $this->installments_paid;
        return $diferencia;
    }

    public function rejectTokenCollect($subscription)
    {
        if (isset($subscription)) {
            foreach ($subscription['instrument'] as $instrument) {
                if ($instrument['keyword'] === "token") {
                    $this->update([
                        'token_collect_para_el_pago' => 'CARD_REJECTED_' . $instrument['value']
                    ]);
                }
            }
        }
    }

    public function approvedTokenCollect($subscription)
    {
        if (isset($subscription)) {
            foreach ($subscription['instrument'] as $instrument) {
                if ($instrument['keyword'] === "token") {
                    $this->update(
                        [
                            'token_collect_para_el_pago' => $instrument['value']
                        ]
                    );
                }
            }
        }
    }
    public function subscriptions()
    {
        return $this->hasMany(PlaceToPaySubscription::class, 'transactionId');
    }

    public function lastRejectedSubscription()
    {
        return $this->hasMany(PlaceToPaySubscription::class, 'transactionId')
            ->where('status', 'REJECTED')
            ->latest('updated_at')
            ->first();
    }

    public function lastApprovedSubscription()
    {
        return $this->hasMany(PlaceToPaySubscription::class, 'transactionId')
            ->where('status', 'APPROVED')
            ->latest('updated_at')
            ->first();
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }
    public function paymentLinks()
    {
        return $this->hasMany(PlaceToPayPaymentLink::class, 'transactionId');
    }
    public static function incrementInstallmentsPaid($sessionId)
    {
        self::where('id', $sessionId)->increment('installments_paid', 1);
    }
    public function getFirstInstallmentPaid()
    {
        return $this->subscriptions()
            ->where('nro_quote', 1)
            ->where('status', 'APPROVED')
            ->get()
            ->first();
    }
    public static function getPaymentDataByRequestId($requestId)
    {
        $session = self::where(['requestId' => $requestId])->first();
        if ($session) {
            $paymentData = json_decode($session->paymentData);
            return $paymentData;
        }
        return null;
    }
    public static function getFullNameFromPaymentData($paymentData)
    {
        if (isset($paymentData->name) && isset($paymentData->surname)) {
            $fullName = $paymentData->name . ' ' . $paymentData->surname;
            return $fullName;
        } else {
            return 'Nombre no disponible';
        }
    }


    public static function checkFirstPaymentWithServiceOf($transaction, $service)
    {
        if ($transaction->subscriptions->count() > 0) {
            $firstSubscription = $transaction->subscriptions->first();

            if ($firstSubscription->status === 'APPROVED') {
                return self::handleApprovedSubscription($firstSubscription);
            }

            $sessionSubscription = $service->getByRequestId($firstSubscription->requestId, $cron = false, $isSubscription = true);
            $statusPayment = self::getStatusPayment($sessionSubscription);

            if ($statusPayment !== 'APPROVED') {
                self::updateSubscriptionStatus($firstSubscription, $sessionSubscription);
                $isTokenRejected = !$service->isRejectedTokenTransaction($transaction);

                if ($statusPayment === 'REJECTED' && $isTokenRejected) {
                    $transaction->update([
                        'token_collect_para_el_pago' => 'CARD_REJECTED_' . $transaction->token_collect_para_el_pago
                    ]);
                }

                return response()->json([
                    "result" => self::$messageOfPtp[$statusPayment],
                    "statusPayment" => $statusPayment,
                ], 400);
            }
        }
    }

    private function handleApprovedSubscription($subscription)
    {
        return response()->json([
            "result" => self::$messageOfPtp[$subscription->status],
            "statusPayment" => 'APPROVED',
        ]);
    }

    private function getStatusPayment($sessionSubscription)
    {
        if (($sessionSubscription['status']['status'] ?? 'DESCONOCIDO') === 'PENDING') {
            return $sessionSubscription['status']['status'];
        }

        return $sessionSubscription['payment'][0]['status']['status'] ?? 'DESCONOCIDO';
    }

    private function updateSubscriptionStatus($subscription, $sessionSubscription)
    {
        $subscription->update([
            'date' => $sessionSubscription['status']['date'],
            'status' => $sessionSubscription['status']['status'],
            'reason' => $sessionSubscription['status']['reason'],
            'message' => $sessionSubscription['status']['message'],
            'authorization' => $sessionSubscription['payment'][0]['authorization'] ?? null,
            'reference' => $sessionSubscription['payment'][0]['reference'] ?? null,
        ]);
    }

    public static function suspend($session)
    {
        $session->update(['status' => 'SUSPEND', 'token_collect_para_el_pago' => null]);
    }

    public static function checkApprovedSessionTryPay($sessionSubscription, $transaction, $service, $renewSuscription = false)
    {
        if ($sessionSubscription['status']['status'] === "APPROVED" && isset($sessionSubscription['subscription'])) {
            foreach ($sessionSubscription['subscription']['instrument'] as $instrument) {
                if ($instrument['keyword'] === "token") {
                    $transaction->update(['token_collect_para_el_pago' => $instrument['value']]);

                    /** @var PlaceToPayService $service */
                    $result = $service->payFirstQuote($sessionSubscription["requestId"], $renewSuscription);

                    if (isset($result['response']['status']['status'])) {
                        $statusPayment = $result['response']['status']['status'];
                    } elseif (isset($result['response']['payment'])) {
                        $statusPayment = $result['response']['payment'][0]['status']['status'] ;
                    } elseif (isset($result['message'])) {
                        $statusPayment = $result['status'];
                    }

                    return [
                        "updateRequestSession" => $transaction,
                        "payment" => $result,
                        "paymentDate" => now(),
                        "result" => self::$messageOfPtp[$statusPayment],
                        "statusPayment" => $statusPayment,
                    ];
                }
            }
        } else {
            return ["sessionPtp" => $sessionSubscription, 'transaction' => $transaction];
        }
    }
}
