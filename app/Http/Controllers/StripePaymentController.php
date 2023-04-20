<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Stripe;
use Stripe\StripeClient;
use Throwable;

class StripePaymentController extends Controller
{

    private $stripe;
    private $stripePlans;

   /*  public function __construct()
    {
        $STRIPE_SECRET = env('APP_DEBUG') ? env('STRIPE_OCEANO_SK_TEST') : env('STRIPE_OCEANO_SK');
        $this->stripe = new StripeClient($STRIPE_SECRET);
    } */

    public function paymentIntent(Request $request)
    {
        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => $request->amount,
                'currency' => $request->currency,
                'payment_method_types' => ['card'],
            ]);

            return response()->json(['response' => $paymentIntent], 201);
        } catch (\Stripe\Exception\CardException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        } catch (\Stripe\Exception\RateLimitException $e) {
            // Too many requests made to the API too quickly
            return ['success' => false, 'error' => $e->getMessage()];
        } catch (\Stripe\Exception\InvalidRequestException $e) {
            // Invalid parameters were supplied to Stripe's API
            return ['success' => false, 'error' => $e->getMessage()];
        } catch (\Stripe\Exception\AuthenticationException $e) {
            // Authentication with Stripe's API failed
            return ['success' => false, 'error' => $e->getMessage()];
        } catch (\Stripe\Exception\ApiConnectionException $e) {
            // Network communication with Stripe failed
            return ['success' => false, 'error' => $e->getMessage()];
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        } catch (Throwable $e) { // For PHP 7
            return ['success' => false, 'error' => $e->getMessage()];
        } catch (Exception $e) { // For PHP 5
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function getStripeAccountByCountry($countryAlpha2Iso){
        $stripeSK = "";

        if($countryAlpha2Iso === 'MX' ){
            $stripeSK = env('APP_DEBUG') ? env('STRIPE_MX_SK_MSK_TEST') : env('STRIPE_MX_SK_MSK_PROD');
          }else{
            $stripeSK = env('APP_DEBUG') ? env('STRIPE_OCEANO_SK_TEST') : env('STRIPE_OCEANO_SK_PROD');
        }

        return  $stripeSK;
    }

    public function subscriptionPayment(Request $request)
    {

        try {

            $stripeConfig = $this->getStripeAccountByCountry($request->country);
            $this->stripe = new StripeClient($stripeConfig);

            $subscriptionPlanId = self::getPlanIdByCountry($request->country, env("APP_DEBUG"));
            $paymentMethod = $this->stripe->paymentMethods->retrieve($request->paymentMethodId, []);

            $customer = $this->findOrCreateCustomerByEmail($request->email, $request->contact, $paymentMethod);

            $paymentMethod->attach(['customer' => $customer->id]);

            $installment_amount = intval(intval($request->amount) / $request->installments);

            $stripeData = [
                "installment_amount" => $installment_amount,
                "installments" => $request->installments,
            ];

            $subscriptionMetadata = ($request->contact != null) ? self::generateMetadataArray($request, $stripeData) : ['origin' => 'Pasarela Cobros Stripe'];

            $subscriptionFinishedAt = strtotime("+" . strval($request->installments) . " months");

            $stripeSubscription = $this->stripe->subscriptions->create([
                'customer' => $customer->id,
                'items' => [[
                    'price' => $subscriptionPlanId,
                    'quantity'  => $installment_amount,
                ],],
                'default_payment_method' => $paymentMethod->id,
                'cancel_at' => $subscriptionFinishedAt,
                'metadata'  => $subscriptionMetadata,
                'payment_behavior' => 'error_if_incomplete',
                'proration_behavior' => 'none',
            ]);

            return response()->json($stripeSubscription);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    private static function generateMetadataArray($requestData, $stripeMetadataData)
    {
        $metadata = array('origin' => 'Super Pasarela de Cobros');
        // dd($requestData->sale);
        $metadata["SO_Number"] = $requestData->sale['SO_Number'];
        $metadataTotalAmount = 0;

        foreach ($requestData->products as $i => $product) {
            $metadataTotalAmount += intval($product['price']);
            $metadata[$i . "_name"] = $product['name'];
            $metadata[$i . "_sku"] = $product['id'];
            $metadata[$i . "_quantity"] = $product['quantity'];
            $metadata[$i . "_total"] = $product['price'];
            $metadata[$i . "_discount_percentage"] = $requestData->sale['Descuento_Plataforma_Pagos'] == NULL ? 0 : $requestData->sale['Descuento_Plataforma_Pagos'];
        }

        $metadata['cuotas'] = $stripeMetadataData['installments'];
        $metadata['monto_total'] =  $metadataTotalAmount;
        $metadata['valor_cuota'] = $stripeMetadataData['installment_amount'];

        return $metadata;
    }



    public function findOrCreateCustomerByEmail($email, $contact, $currentPaymentMetohd)
    {
        $customers = $this->stripe->customers->all(['email' => $email]);

        if (!empty($customers->data)) {
            foreach ($customers->data as $customer) {
                return $this->stripe->customers->retrieve($customer->id);
            }
        } else {
            $newCustomer = $this->stripe->customers->create([
                'email' => $email,
                'name' => $contact['Full_Name'],
                'payment_method' => $currentPaymentMetohd->id,
            ]);

            return $newCustomer;
        }

        //return response()->json(["message" => "No customer found by $email"], 500);
    }

    public function getAllPlans()
    {
        $this->stripePlans = $this->stripe->plans->all();
        return $this->stripePlans;
    }

    public function getAPlan($plan_id)
    {
        $this->stripePlans = $this->stripe->plans->retrieve($plan_id, []);
        return $this->stripePlans;
    }

    private static function getPlanIdByCountry($country, $is_test_environment)
    {

        $answer='';
        switch ($country) {
            case 'AR': {
                    $answer = $is_test_environment ? 'plan_HIbEiKOss32TFv' : 'plan_IBc208sWxXoqSX';
                    break;
                } //Argentina
            case 'ur': {
                    $answer = $is_test_environment ? 'plan_HHVXws46gijnOU' : 'plan_HIlqECniUZ2kFb';
                    break;
                } //Uruguay
            case 'CL': {
                    $answer = $is_test_environment ? 'plan_HHVYpZ6DNzgawx' : 'price_1Gxl0JBZ0DURRH2FdpW5kUuL';
                    break;
                } //Chile
            case 'pe': {
                    $answer = $is_test_environment ? 'plan_HHVWiCk3XFBgUn' : 'price_HJCn7S4fnRLHwO';
                    break;
                } //Perú
            case 'MX': {
                    $answer = $is_test_environment ? "price_1Myy67LfB7wzWQD4JiGWIE8E" : 'price_1MyxzxLfB7wzWQD4eU6orxav'/* 'price_1HcZMpBZ0DURRH2FIqO53wvW' */;
                    break;
                } //Mexico
            case 'co': {
                    $answer = $is_test_environment ? 'plan_HHVShCrYkbvrRF' : 'price_HJZJEiPbNg2FXu';
                    break;
                } // Colombia
            case 'pnm': {
                    $answer = $is_test_environment ? 'plan_HHVZXGf5PHlRVM' : 'price_HJH0jgSVSdq0vs';
                    break;
                } //Panamá
            case 'bo': {
                    $answer = $is_test_environment ? 'price_1H3S8iBZ0DURRH2FhVOC3nCZ' : 'price_1H30abBZ0DURRH2Fi49CMqre';
                    break;
                } //Bolivia
            case 'pa': {
                    $answer = $is_test_environment ? /* 'price_1IEpd0BZ0DURRH2F3N3KjIqf' */ 'price_1KX8J9BZ0DURRH2F6mU3FRoP' : /* OLD 'price_1IEpdJBZ0DURRH2Ft50SjSy3' */ 'price_HJZKilKlCv4X5v';
                    break;
                } //Paraguay
            case 'usa': {
                    $answer = $is_test_environment ? 'price_1HmW1tBZ0DURRH2FjgfveOUk' : 'price_1HmW26BZ0DURRH2FSuzAMjOv';
                    break;
                } //USA
            case 'cr': {
                    $answer = $is_test_environment ? 'price_1IEpeYBZ0DURRH2FFAabUAMJ' : 'price_1IEpenBZ0DURRH2Fz8HdN2N0';
                    break;
                } //Costa Rica
            case 'cu': {
                    $answer = $is_test_environment ? 'price_1IEpfNBZ0DURRH2FYIDusFH4' : 'price_1IEpflBZ0DURRH2FQTYnDOjq';
                    break;
                } //Cuba
            case 'sv': {
                    $answer = $is_test_environment ? 'price_1IEpgRBZ0DURRH2FsN0yARio' : 'price_1IEpgsBZ0DURRH2FcMPqfJoG';
                    break;
                } //El Salvador
            case 'gu': {
                    $answer = $is_test_environment ? 'price_1H3SA4BZ0DURRH2FguR39sjM' : 'price_1H3S0vBZ0DURRH2Fzeg4prPf';
                    break;
                } //Guatemala
            case 'hn': {
                    $answer = $is_test_environment ? 'price_1H3SANBZ0DURRH2FnRG34HhX' : 'price_1H30gdBZ0DURRH2FU4tPMS51';
                    break;
                } //Honduras
            case 'ni': {
                    $answer = $is_test_environment ? 'price_1IEq6oBZ0DURRH2FYTeuQKHM' : 'price_1IEq5uBZ0DURRH2FwbkFYbMw';
                    break;
                } //Nicaragua
            case 'rd': {
                    $answer = $is_test_environment ? 'price_1IEpopBZ0DURRH2F5E5Ylma9' : 'price_1IEpp0BZ0DURRH2FOuF5VnJi';
                    break;
                } //República Dominicana
            case 've': {
                    $answer = $is_test_environment ? 'price_1IEpprBZ0DURRH2FocVWSQcI' : 'price_1IEpq1BZ0DURRH2FMHPMwbz6';
                    break;
                } //Venezuela
        }
        return $answer;
    }
}
