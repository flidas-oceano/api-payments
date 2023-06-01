<?php

namespace App\Http\Controllers;

use App\Models\MercadoPagoPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MercadoPagoPaymentController extends Controller
{
    public function searchPaymentApprove(Request $request, $so)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('MP_MX_KEY')
        ])->get('https://api.mercadopago.com/v1/payments/search', [
                'limit' => 1000,
                'external_reference' => $so
            ]);

        if ($response->ok()) {
            $payments = collect($response->json()['results']);

            $approvedPayment = $payments->where('status', 'approved')->sortBy('date_approved')->first();
            $rejectedPayment = $payments->where('status', 'rejected')->first();

            if ($approvedPayment) {
                // Se encontro un pago en MP APROBADO
                $paymentMP = [
                    'sub_id' => $approvedPayment['point_of_interaction']['transaction_data']['subscription_id'],
                    'so' => str_replace("x", "", $approvedPayment['external_reference']),
                    'event_id' => $approvedPayment['id'],
                    'status' => $approvedPayment['status'],
                    'status_detail' => $approvedPayment['status_detail'],
                    'date_approved' => $approvedPayment['date_approved'],
                    'send_crm' => false
                ];

                return MercadoPagoPayment::updateOrCreate(['so' => $paymentMP['so']], $paymentMP);
            } elseif ($rejectedPayment) {
                //No se encontro ningun pago APROBADO
                $paymentMP = [
                    'sub_id' => $rejectedPayment['point_of_interaction']['transaction_data']['subscription_id'],
                    'so' => str_replace("x", "", $rejectedPayment['external_reference']),
                    'event_id' => $rejectedPayment['id'],
                    'status' => $rejectedPayment['status'],
                    'status_detail' => $rejectedPayment['status_detail'],
                    'date_approved' => $rejectedPayment['date_approved'],
                    'send_crm' => false
                ];

                return MercadoPagoPayment::updateOrCreate(['so' => $paymentMP['so']], $paymentMP);

            }

        } else {
            // Manejar la respuesta de error

            return response()->json([
                "response_of_mp" => $response,
                "message" => "No se encontraron pagos en la busqueda"
            ], 404);
        }
    }


}