<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'entity_id_crm',
        'installments',
        'Fecha_de_Vto',
        'lead_source',
        'name',
        'address',
        'payment_type',
        'country',
        'is_sub',
        'payment_in_advance',
        'left_installments',
        'left_payment_type',
        'currency',
    ];
    private static $formAttributes = [
        'id',
        'installments',
        'Fecha_de_Vto',
        'lead_source',
        'name',
        'address',
        'payment_type',
        'country',
        'is_sub',
        'payment_in_advance',
        'left_installments',
        'left_payment_type',
        'currency'
    ];
    protected $table = 'contracts';
    public $hidden = ['created_at', 'updated_at', 'products'];

    public static function getFormAttributes()
    {
        return self::$formAttributes;
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'contract_id', 'id');
    }

    public static function getProducts($lineItems)
    {
        $answer = [];
        foreach ($lineItems as $p) {
            $newP = [];
            $newP['name'] = $p->getProduct()->getLookupLabel();
            $newP['quantity'] = $p->getQuantity();
            $newP['id'] = $p->getId();
            $newP['price'] = $p->getNetTotal();

            $answer[] = $newP;

        }
        return $answer;

    }
    public static function mappingDataContract($request, $gateway)
    {
        if (boolval($request->is_suscri)) {
            $modoDePago = 'Cobro recurrente';
            if (boolval($request->is_advanceSuscription)) {
                $modoDePago = $modoDePago . ' con parcialidad';
            }
        } else {
            $modoDePago = 'Cobro total en un pago';
        }

        if ($gateway == 'CTC') {
            return [
                // Contrato
                'Monto_de_parcialidad' => $request->installment_amount,
                'Seleccione_total_de_pagos_recurrentes' => strval($request->installments),
                'Monto_de_cada_pago_restantes' => $request->is_advanceSuscription ? $request->payPerMonthAdvance : $request->installment_amount,
                'Cantidad_de_pagos_recurrentes_restantes' => strval($request->installments - 1),
                'Fecha_de_primer_cobro' => date('Y-m-d'),
                'Status' => 'Aprobado',
                'M_todo_de_pago' => $gateway,
                'Modo_de_pago' => $modoDePago,

                //campos CTC
                'folio_suscripcion' => $request->subscriptionId,
                'folio_pago' => $request->folio_pago,
            ];
        }

        if ($gateway == 'Placetopay') {
            $session = PlaceToPayTransaction::where(['requestId' => $request->requestId])->first();

            if($session->isOneTimePayment()){
                $Fecha_de_primer_cobro = $session->date;
            }else{
                $subscription = $session->lastApprovedSubscription();
                $Fecha_de_primer_cobro = $subscription->date_to_pay;
            }
            return [
                'Monto_de_parcialidad' => $session->first_installment,
                'Seleccione_total_de_pagos_recurrentes' => strval($session->quotes),
                'Monto_de_cada_pago_restantes' => $session->remaining_installments,
                'Cantidad_de_pagos_recurrentes_restantes' => strval($session->quotes - 1),
                'Fecha_de_primer_cobro' => date('Y-m-d', strtotime($Fecha_de_primer_cobro)),
                'Status' => 'Aprobado',
                'M_todo_de_pago' => $gateway,
                'Modo_de_pago' => $modoDePago,
                'stripe_subscription_id' => $session->reference,
            ];
        }

        return [
            'Monto_de_parcialidad' => $request->installment_amount,
            'Seleccione_total_de_pagos_recurrentes' => strval($request->installments),
            'Monto_de_cada_pago_restantes' => $request->is_advanceSuscription ? $request->payPerMonthAdvance : $request->installment_amount,
            'Cantidad_de_pagos_recurrentes_restantes' => strval($request->installments - 1),
            'Fecha_de_primer_cobro' => date('Y-m-d'),
            'Status' => 'Aprobado',
            'M_todo_de_pago' => $gateway,
            'Modo_de_pago' => $modoDePago,
            'stripe_subscription_id' => $request->subscriptionId,
        ];
    }

    public static function buildDetailApprovedPayment($request){

        $session = PlaceToPayTransaction::where(['requestId' => $request->requestId])->first();

        if($session->isOneTimePayment()){
            $Fecha_Cobro = date('Y-m-d', strtotime($session->date));
            $Cobro_ID = $session->reference;
            $Monto = $session->total;
            $Numero_de_cobro = 1;
        }else{
            $subscription = $session->lastApprovedSubscription();
            $Fecha_Cobro = date('Y-m-d', strtotime($subscription->date_to_pay));
            $Cobro_ID = $subscription->reference;
            $Monto = $subscription->total;
            $Numero_de_cobro = $subscription->nro_quote;
        }

        // $detailApprovedPayment
        return [
            'Fecha_Cobro' => $Fecha_Cobro,
            'Num_de_orden_o_referencia_ext' => $session->reference,
            'Cobro_ID' => $Cobro_ID,
            'Monto' => $Monto,
            'Numero_de_cobro' => $Numero_de_cobro,
            'Origen_Pago' => 'SPP'
        ];
    }
}
