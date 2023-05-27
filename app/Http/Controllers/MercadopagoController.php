<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\{CronosElements};
use Illuminate\Support\Facades\Storage;
use zcrmsdk\crm\crud\ZCRMInventoryLineItem;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\oauth\ZohoOAuth;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\exception\ZCRMException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use MercadoPago;

class MercadopagoController extends Controller
{

    private function credentials($country)
    {
        switch($country)
        {
            case 'mx_msk': return('APP_USR-6404915214202963-041914-6248701ac1af4c59715f9408b68db885-1350977988');
            
        }
    }

    public function retrieveEvent($id, $country)
    {
        MercadoPago\SDK::setAccessToken($this->credentials($country));
        $payment = MercadoPago\Payment::find_by_id($id);
    }

    public function createPlan($data,$country)
	{
		$plan = 'e';
		
		try
		{

			MercadoPago\SDK::setAccessToken($this->credentials($country));
			
			$plan = new MercadoPago\Preapproval();

			$plan->auto_recurring = $data['auto_recurring'];
			
			$plan->back_url = 'https://oceanomedicina.com.ar/gracias';
			$plan->reason = 'Cursos Medical Scientific Knowledge';
			$plan->external_reference = $data['external_reference'];
			$plan->payer_email = $data['payer_email'];
			
			$plan->save();
		}
		catch(\Exception $e)
		{
			$plan = 'MP ERROR: ' . $e->getMessage();
			//echo 'aaaaaaaaa';
			//dd($e);

			Log::error($e);
		}
		
		return($plan);
	}

    public function createCheckout($data,$country)
	{

		$checkout = 'e';
		
		$data['notification_url'] = 'https://www.oceanomedicina.net/api-payments2/public/api/hook_mx';

		try
		{
			MercadoPago\SDK::setAccessToken($this->credentials($country));
			
			// Create a preference object
			$preference = new MercadoPago\Preference();

			// Create a preference item
			$item = new MercadoPago\Item();
			$item->title = 'Cursos Medical Scientific Knowledge';
			$item->id = 1;
			$item->quantity = 1;
			$item->unit_price = $data['unit_price'];
			$item->currency_id = 'MXN';
			$preference->items = array($item);
			$preference->external_reference = $data['external_reference'];
			$preference->notification_url =$data['notification_url'];
			$preference->save();
		}
		catch(\Exception $e)
		{
			$checkout = 'MP ERROR: ' . $e->getMessage();
			//echo 'aaaaaaaaa';
			$this->log($e);
			$this->log($data);
		}
		
		return($preference);
		
	}

}