<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\{Pasarelaux};
use Illuminate\Support\Facades\Storage;
use zcrmsdk\crm\crud\ZCRMInventoryLineItem;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\oauth\ZohoOAuth;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\exception\ZCRMException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;

class GeneratorController extends Controller
{
	public $Mercadopago;

	public function __construct()
    {
        $this->Mercadopago = App::make('App\Http\Controllers\MercadopagoController');
	}

	private function sortCountries($iso)
	{
		$answer = strtolower($iso);
		
		switch(strtolower($iso))
		{
			case 'cl': {$answer = 'ch'; break;};
			case 'uy': {$answer = 'uy'; break;};
		}
		
		return($answer);
	}

	private function preparePlan($post)
	{
		$auto_recurring = [];
		$data = [];
		
		$day = date('d');
		
		$total = $post['amount'];
		$amount = round($total/$post['months']);
		$country = $this->sortCountries($post['country']);
		
		if($day > 28)
			$day = 28;
		
		$auto_recurring['frequency'] = 1;
		$auto_recurring['frequency_type'] = 'months';
		$auto_recurring['repetitions'] = $post['months'];
		//$auto_recurring['billing_day'] = $day;
		$auto_recurring['transaction_amount'] = (float) $amount;
		//$auto_recurring['currency_id'] = 'CLP';
		
		if($country == 'ar')
			$auto_recurring['currency_id'] = "ARS";
		else if($country == 'mx' || $country == 'mx_msk')
			$auto_recurring['currency_id'] = "MXN";
		else if($country == 'ch')
			$auto_recurring['currency_id'] = "CLP";
		
		$data['auto_recurring'] = $auto_recurring;
		
		$data['back_url'] = 'https://oceanomedicina.com.ar/gracias';
		$data['reason'] = 'Curso de Océano Medicina';
		$data['external_reference'] = 'x'.$post['so'];

		$data['payer_email'] = $post['email'];
		
		
		if (strpos($country, 'msk') !== false) 
		{
			$data['reason'] = 'Cursos Medical Scientific Knowledge';
		}

		$answer = $this->Mercadopago->createPlan($data,$country);

		return($answer);
	}
	
	private function prepareCheckout($load)
	{
		$country = $this->sortCountries($load['country']);
		
		$data = [];
		
		$back_urls = [];
		$back_urls['success'] = '';
		$back_urls['pending'] = '';
		$back_urls['failure'] = '';
		
		$data['back_urls'] = $back_urls;
		$data['external_reference'] = $load['so'];
		
		$item = [];
		$item['id'] = 1;
		$item['title'] = 'Curso de Océano Medicina';
		$item['quantity'] = 1;
		//$item['currency_id'] = 'ARS';
		//$item['unit_price'] = (float)$load['amount'];
		$data['unit_price'] = (float)$load['amount'];
		
		if($country == 'ar')
			$item['currency_id'] = "ARS";
		else if($country == 'mx' || $country == 'mx_msk')
			$item['currency_id'] = "MXN";
		else if($country == 'ch')
			$item['currency_id'] = "CLP";
		
		$data['items'] = [];
		$data['items'][] = $item;
		
		//$data['metadata'] = [];
		//$data['metadata']['payment_process'] = p_SUPER;

		if (strpos($country, 'msk') !== false) 
		{
			$data['title'] = 'Cursos Medical Scientific Knowledge';
		}

		$answer = $this->Mercadopago->createCheckout($data,$country);

		return($answer);
	}
	
	private function checkCall($post)
	{
		$answer = ['status' => 1, 'error' => null];
		
		if(!isset($post['type']))
		{
			$answer['error'] = 'no type set';
			$answer['status'] = 0;
		}
		else if($post['type'] != 'trad' && $post['type'] != 'susc')
		{
			$answer['error'] = 'unknown type';
			$answer['status'] = 0;
		}
		else if($post['type'] == 'susc')
		{
			$neededSusc = ['fullname','address','dni','phone','months','amount','so','sale_id','mail'];
			
			//check needed values
			foreach($neededSusc as $n)
				if (!isset($post[$n]))
				{
					$answer['error'] = 'missing ' . $n;
					$answer['status'] = 0;
					break;
				}
			
		}
		else if($post['type'] == 'trad')
		{
			$neededTrad = ['fullname','address','dni','phone','amount','so','sale_id','mail'];
			
			//check needed values
			foreach($neededTrad as $n)
				if (!isset($post[$n]))
				{
					$answer['error'] = 'missing ' . $n;
					$answer['status'] = 0;
					break;
				}
		}
		
		return($answer);
	}

	//para crear preferencia o plan, chile
	public function generateCheckoutPro(Request $request)
	{
		$POST = $request->all();

		Log::info('CHKT PRO arrives',$POST);

		$dom = 'test';
		
		$response = [];
		
		$check = $this->checkCall($POST);
		
		if($check['status'] == 1)
		{
			
			if($POST['type'] == 'trad')
				$request = $this->prepareCheckout($POST);
			else if($POST['type'] == 'susc')
				$request = $this->preparePlan($POST);		
				
			if(isset($request->id))
			{
				if($POST['type'] == 'trad')
				{
					if($dom == 'test')
						$response['url'] = $request->sandbox_init_point;

					$response['id'] = $request->id;
				} 
				else if($POST['type'] == 'susc')
				{
					
					$response['url'] = $request->init_point;
					$response['id'] =  $request->id;
					
				}	
				
				$this->saveInBD($POST);
			}
			else
			{
				$response = ['error' => $request, 'status' => 0];
			}
			
		}
		else
		{
			$response = $check;
		}
		
		Log::info("CHKT PRO response",$response);

		return response()->json($response);
		
	}
	
	private function saveInBD($post)
	{
		$pasa = new Pasarelaux();

		$pasa->sale_id = $post['sale_id'];
		$pasa->data = json_encode($post);

		$pasa->save();
	}

	


}