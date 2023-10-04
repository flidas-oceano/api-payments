<?php

namespace App\Models;

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
    function isSubscription() {
        return ($this->type === 'requestSubscription') ? true: false;
    }
    function isAdvancedSubscription() {
        return $this->first_installment !== null;
    }
    function installmentsToPay() {
        $diferencia = $this->quotes -  $this->installments_paid;
        return $diferencia;
    }
    public function subscriptions()
    {
        return $this->hasMany(PlaceToPaySubscription::class, 'transactionId');
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
        // PlaceToPayTransaction::find($session->id)->update(['installments_paid' => DB::raw('COALESCE(installments_paid, 0) + 1')]);
    }
    public function getFirstInstallmentPaid()
    {
        return $this->subscriptions()
        ->where('nro_quote' , 1)
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
}
