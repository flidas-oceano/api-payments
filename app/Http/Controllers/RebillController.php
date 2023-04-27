<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RebillController extends Controller
{
    public function __construct()
    {
        \Rebill\SDK\Rebill::getInstance()->setProp([
            'user' => 'rofloresfraysse@gmail.com',
            'pass' => 'Ro28826813!',
            'orgAlias' => 'unomasuno',
            'orgId' => 'c14f14fe-03f9-45e5-b83f-166107567e06'
        ]);

        define('REBILL_GATEWAY_ID', 'cc9b8f6a-4077-475a-abba-38073db06c83');
        define('REBILL_GATEWAY_CURRENCY', 'USD');
    }

    public function hola()
    {
        echo ' ay holi';

        
        $checkout = new \Rebill\SDK\Models\Checkout();
        $checkout->amount = 100.0;
        $checkout->currency = 'USD';
        $checkout->description = 'Test checkout';

        $checkout->redirectUrl = 'https://example.com/checkout/success';

        $response = $checkout->create($checkout);
        echo 'Checkout URL: ' . $response->url;
        

        $customer = $this->createCustomer(['email' => 'test@gm.com', 
                                    'first_name' => 'test',
                                    'last_name' => 'tost',
                                    'country' => 'MX']);


        dd($customer);
        
    }


    private function createCustomer($data)
    {
        $answer = 'error';

        $customer = new \Rebill\SDK\Models\Customer();
        $customer->email = $data['email'];
        $customer->firstName = $data['first_name'];
        $customer->lastName = $data['last_name'];
        $customer->address = [
           /* 'line1' => '123 Main St',
            'line2' => 'Apt 4B',
            'city' => 'Anytown',
            'state' => 'NY',
            'zip' => '12345',*/
            'country' => $data['country']
        ];

        $response = $customer->create();

        if(isset($response->data->id))
            $answer = $response->data->id;
        
        return($answer);
    }

}


