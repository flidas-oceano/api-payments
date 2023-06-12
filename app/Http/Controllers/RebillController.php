<?php

namespace App\Http\Controllers;

use Storage;
use Exception;
use Rebill\SDK\Rebill;
use Illuminate\Http\Request;
use Rebill\SDK\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Rebill\SDK\Models\GatewayStripe;

class RebillController extends Controller
{
    /*    public function __construct()
       {
           Rebill::getInstance()->isDebug = true;
           Rebill::getInstance()->setProp([
               'user' => env('REBILL_MSKLATAM_USER_TEST'),
               'pass' => env('REBILL_MSKLATAM_PW_TEST'),
               'orgAlias' => env('REBILL_MSKLATAM_ORG_TEST'),
               'orgId' => env('REBILL_MSKLATAM_ORG_ID_TEST') //'c14f14fe-03f9-45e5-b83f-166107567e06'
           ]);
           Rebill::getInstance()->setCallBackDebugLog(function ($data) {
               file_put_contents(storage_path('logs/rebill.log'), '---------- ' . date('c') . " -------------- \n$data\n\n", FILE_APPEND | LOCK_EX);
           });

       } */

    public function login()
    {
        $orgAlias = env('REBILL_MSKLATAM_ORG_TEST');

        $response = Http::post('https://api.rebill.to/v2/auth/login/' . $orgAlias, [
            'email' => env('REBILL_MSKLATAM_USER_TEST'),
            'password' => env('REBILL_MSKLATAM_PW_TEST'),
        ])->object();


        /*
        $checkout = new \Rebill\SDK\Models\Checkout();
        $checkout->amount = 100.0;
        $checkout->currency = 'USD';
        $checkout->description = 'Test checkout';
        $token = $response->authToken;
        Storage::disk('local')->put('rebill/token.txt', $token);
        return $response;
        //$response = $checkout->create($checkout);
        // echo 'Checkout URL: ' . $response->url;
        $customer = $this->createCustomer(['email' => 'test@gm.com',
        'first_name' => 'test',
        'last_name' => 'tost',
        'country' => 'MX']);
        dd($customer);
        */

    }

    public function getAllCustomers()
    {
        $token = Storage::disk('local')->get('rebill/token.txt');

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'authorization' => 'Bearer ' . $token,
        ])->get("https://api.rebill.to/v2/customers");

        return $response->json();
    }

    private function getRequestAPI($endpoint)
    {
        $token = Storage::disk('local')->get('rebill/token.txt');

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'authorization' => 'Bearer ' . $token,
        ])->get("https://api.rebill.to/v2" . $endpoint)->object();

        return $response;
    }

    private function postRequestAPI($endpoint, $data)
    {
        $token = Storage::disk('local')->get('rebill/token.txt');

        $response = Http::withHeaders([
            'accept' => 'application/json',
            'authorization' => 'Bearer ' . $token,
        ])->post("https://api.rebill.to/v2" . $endpoint, get_object_vars($data))->object();

        return $response;
    }

    public function generateCheckourRebill(Request $request)
    {
        $customerData = (object) [
            'user' => (object) [
                "email" => "tomasgomez@asd.com",
                "password" => "P@ssw0rd"
            ],
            'profile' => (object) [
                "firstName" => "Tomas",
                "lastName" => "Gomez",
                "personalId" => (object) ["type" => "DNI", "value" => "11111111"],
                "phone" => (object) [
                    "countryCode" => "54",
                    "areaCode" => "11",
                    "phoneNumber" => "11111111"
                ],
                "address" => (object) [
                    "street" => "Miro",
                    "city" => "Ramos Mejia",
                    "state" => "Buenos Aires",
                    "country" => "MX",
                    "zipCode" => "1754",
                    "number" => "2008"
                ],
            ],
            'cards' => []
        ];

        $customer = $this->findOrCreateCustomer($customerData);

        // Creamos un Item con un Price (Si aun no existe)
        $result = (new \Rebill\SDK\Models\Item)->setAttributes([
            'name' => 'Testing checkout',
            'description' => 'Test of Checkout',
            'metadata' => [
                'key_of_meta1' => 'example meta 1',
                'key_of_meta2' => 'example meta 2',
            ],
            'prices' => [
                (new \Rebill\SDK\Models\Price)->setAttributes([
                    'amount' => '2.5',
                    'type' => 'fixed',
                    'description' => 'Example of Subscription with infinite repetitions',
                    'frequency' => (new \Rebill\SDK\Models\Shared\Frequency)->setAttributes([
                        'type' => 'months',
                        'quantity' => 3
                    ]),
                    'repetitions' => null,
                    'currency' => env('REBILL_GATEWAY_CURRENCY'),
                    'gatewayId' => env('REBILL_GATEWAY_MP_ID')
                ]),
                (new \Rebill\SDK\Models\Price)->setAttributes([
                    'amount' => 1.5,
                    'type' => 'fixed',
                    'description' => 'Example of Subscription with only two payment',
                    'frequency' => (new \Rebill\SDK\Models\Shared\Frequency)->setAttributes([
                        'type' => 'months',
                        'quantity' => 2
                    ]),
                    'repetitions' => 2,
                    'currency' => env('REBILL_GATEWAY_CURRENCY'),
                    'gatewayId' => env('REBILL_GATEWAY_MP_ID')
                ]),
                (new \Rebill\SDK\Models\Price)->setAttributes([
                    'amount' => 0.5,
                    'type' => 'fixed',
                    'description' => 'Example of Unique Payment',
                    'frequency' => (new \Rebill\SDK\Models\Shared\Frequency)->setAttributes([
                        'type' => 'months',
                        'quantity' => 1
                    ]),
                    'repetitions' => 1,
                    'currency' => env('REBILL_GATEWAY_CURRENCY'),
                    'gatewayId' => env('REBILL_GATEWAY_MP_ID')
                ]),
            ]
        ])->create();


        $prices = [];
        foreach ($result->prices as $p) {
            $prices[] = (new \Rebill\SDK\Models\Shared\CheckoutPrice)->setAttributes([
                'id' => $p->id,
                'quantity' => 1
            ]);
        }

        $checkout = (new \Rebill\SDK\Models\Checkout())->setAttributes([
            'prices' => $prices,
            'customer' => (new \Rebill\SDK\Models\Shared\CheckoutCustomer)->setAttributes([
                'email' => 'testuser@clientdomain.com',
                'firstName' => 'APRO Test',
                'lastName' => 'Name',
                'phone' => [
                    "countryCode" => "52",
                    // Optional with this value: "-"
                    "areaCode" => "1",
                    // Optional with this value: "-"
                    "phoneNumber" => "302390203929039"
                ],
                'address' => [
                    "street" => "San Jose",
                    "number" => "1120",
                    // Optional with this value: "0"
                    "state" => "Buenos Aires",
                    "city" => "San Isidro",
                    "country" => "AR",
                    "zipCode" => "2000"
                ],
                'taxId' => [
                    'type' => 'CUIT',
                    // See Rebill\SDK\Models\GatewayIdentificationTypes::get
                    'value' => '222999333'
                ],
                'personalId' => [
                    'type' => 'DNI',
                    // See Rebill\SDK\Models\GatewayIdentificationTypes::get
                    'value' => '111222333'
                ],
                'card' => (new \Rebill\SDK\Models\Card)->setAttributes([
                    'cardNumber' => '4509953566233704',
                    'cardHolder' => [
                        'name' => 'APRO Test Name',
                        'identification' => [
                            'type' => 'DNI',
                            // See Rebill\SDK\Models\GatewayIdentificationTypes::get
                            'value' => '111222333'
                        ]
                    ],
                    'securityCode' => '123',
                    'expiration' => [
                        'month' => 11,
                        'year' => 2025
                    ],
                ])
            ])
        ]);
        /* $checkout->amount = 100.0;
        $checkout->currency = 'MXN';
        $checkout->description = 'Test checkout';
        $checkout->customer = [
        'email' => 'testuser@clientdomain.com',
        'firstName' => 'APRO Test',
        'lastName' => 'Name',
        'phone' => [
        "countryCode" => "52",
        // Optional with this value: "-"
        "areaCode" => "1",
        // Optional with this value: "-"
        "phoneNumber" => "302390203929039"
        ],
        'address' => [
        "street" => "San Jose",
        "number" => "1120",
        // Optional with this value: "0"
        "state" => "Buenos Aires",
        "city" => "San Isidro",
        "country" => "AR",
        "zipCode" => "2000"
        ],
        'taxId' => [
        'type' => 'CUIT',
        // See Rebill\SDK\Models\GatewayIdentificationTypes::get
        'value' => '222999333'
        ],
        'personalId' => [
        'type' => 'DNI',
        // See Rebill\SDK\Models\GatewayIdentificationTypes::get
        'value' => '111222333'
        ],
        'card' => (new \Rebill\SDK\Models\Card)->setAttributes([
        'cardNumber' => '4509953566233704',
        'cardHolder' => [
        'name' => 'APRO Test Name',
        'identification' => [
        'type' => 'DNI',
        // See Rebill\SDK\Models\GatewayIdentificationTypes::get
        'value' => '111222333'
        ]
        ],
        'securityCode' => '123',
        'expiration' => [
        'month' => 11,
        'year' => 2025
        ],
        ])
        ]; */
        $checkout->redirectUrl = 'http://example.com/checkout/success';

        $response = $checkout->create($checkout);
        //  echo 'Checkout URL: ' . $response->url;




        return response()->json([
            "checkout_url" => $response->url,
            //   'customer' => $customer
        ]);

    }


    private function findOrCreateCustomer($customerData)
    {
        //Primero buscamos al customer por su email
        $hasCustomer = $this->getRequestAPI('/customers/' . $customerData->user->email . '/detail');

        if (isset($hasCustomer->statusCode) && $hasCustomer->statusCode === 404) {
            // Creo customer que no encuentro
            $newCustomer = $this->postRequestAPI('/customers', $customerData);
            return $newCustomer;
        }

        return $hasCustomer;
    }

    public function addStripeGateway()
    {
        $result = (new GatewayStripe)->setAttributes([
            'privateKey' => env('STRIPE_MX_SK_MSK_TEST'),
            'publicKey' => env('STRIPE_MX_PK_MSK_TEST'),
            'description' => 'Test MSKLATAM Stripe'
        ])->add('MX'); //ISO2 of Country

        dump($result);
    }

    public function checkPendingPayments()
    {
        $pendingPayments = DB::table('pending_payments_rebill')->get();
        $token = env('REBILL_TOKEN_PRD');

        foreach ($pendingPayments as $payment) {
            $response = Http::withHeaders([
                'accept' => 'application/json',
                'authorization' => 'Bearer ' . $token,
            ])->get("https://api.rebill.to/v2/payments/" . $payment->payment_id)->json();
        }
    }

    public function addPendingPayment(Request $request)
    {
        $payment = $request->payment;

        $response = DB::table('pending_payments_rebill')->insert([
            'payment_id' => $payment['id'],
            'status' => $payment['status'],
        ]);

        return response()->json($response);
    }
}