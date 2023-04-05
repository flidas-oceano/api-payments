<?php

namespace App\Http\Controllers;


use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DatafastController extends Controller
{

    public function __construct()
    {
       
    }

    private function getUserIpAddr()
    {
        if(!empty($_SERVER['HTTP_CLIENT_IP'])){
            //ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
            //ip pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }else{
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public function requestForm(Request $info)
	{
		$answer = 'error';
		
		
		//Log::datafast("DATAFAST = form&cart " . json_encode($info));
		
		$FORM = $info['forms']; //data de los forms
		$CART = $info['cart'];
		
		$givenName = $FORM['datosadicionales']['nombrealumno'];
		$middleName = '';
		$surname = $FORM['datosadicionales']['apellidoalumno'];
		$ip = $this->getUserIpAddr();
		$merchantCustomerId = $this->shortMailHash($FORM['datosadicionales']['email']);
		$merchantTransactionId = bin2hex(random_bytes(100));
		$email = $FORM['datosadicionales']['email'];
		$identificationDocId = $this->filterDocId($FORM['datosadicionales']['dnialumno']);
		
		$products = $this->prepareProducts($CART['products']);
		
		$phone = $FORM['datosadicionales']['telefonoalumno'];
	    $shippingStreet = '';
		$billingStreet = $this->filterStreet($FORM);
		$billingCountry = 'EC';
		$shippingCountry = '';
		
		
		$amount = $CART['total'];
		
		//suscri
		$amount = $amount / $FORM['meses']['meses_cre'];
		$amount = round($amount,2);
		
		$amount = $this->formatAmount($amount);
		
		$isDebug = env('APP_DEBUG');
		
		if($isDebug)
		{
			$url = "https://eu-test.oppwa.com/v1/checkouts";
			$testMode = '&testMode=EXTERNAL';
			$MID = '1000000505';
			$TID = 'PD100406';
			$verifyPeer = false;
			$entityId = '8ac7a4c7803f575f018042ab82fb0916';
			$accessToken = 'OGE4Mjk0MTg1YTY1YmY1ZTAxNWE2YzhjNzI4YzBkOTV8YmZxR3F3UTMyWA==';
			$amount = $this->formatAmount($amount/100);
		}
		else
		{
			$url = "https://eu-prod.oppwa.com/v1/checkouts";
			$testMode = '';
			$MID = '4200005199';
			$TID = 'BP407014';
			$verifyPeer = true;
			$entityId = '8acda4c881aeabd90181b020482a0fdc';
			$accessToken = 'OGFjZGE0Yzg4MWFlYWJkOTAxODFiMDFmYjM4MDBmYzN8QjlRYzlUTnJjWg==';
		}
		
		
		$data = "entityId=". $entityId .
		 "&customParameters[SHOPPER_MID]=" . $MID . 
		 "&customParameters[SHOPPER_TID]=" . $TID . 
		 "&customParameters[SHOPPER_ECI]=0103910" .
		 "&customParameters[SHOPPER_PSERV]=17913101" .
		 "&customParameters[SHOPPER_VERSIONDF]=2" .
		 "&risk.parameters[USER_DATA2]=OCEANOMEDICINA" .
		 "&customer.givenName=" . $givenName .
		 "&customer.middleName=" .
		 "&customer.surname=" . $surname .
		 "&customer.ip=" . $this->getUserIpAddr() . 
		 "&customer.merchantCustomerId=" . $merchantCustomerId .  
		 "&merchantTransactionId=" . $merchantTransactionId .
		 "&customer.email=" . $email . 
		 "&customer.identificationDocType=IDCARD" .
		 "&customer.identificationDocId=" . $identificationDocId .
		 "&customer.phone=" . $phone .
		 "&shipping.street1=" .
		 "&billing.street1=" . $billingStreet . 
		 "&shipping.country=&" . $products .
		 "customParameters[SHOPPER_VAL_BASE0]=" . $amount .
		 "&customParameters[SHOPPER_VAL_BASEIMP]=0.00" .
		 "&customParameters[SHOPPER_VAL_IVA]=0.00" . $testMode . 
		 "&amount=" . $amount .
		 "&currency=USD" .
		 "&paymentType=DB";
		 
		 
		 //LOGEAR DATA PARA VER QUE LE ESTA MANDANDO
		 //Log::datafast("DATAFAST = request_checkout_data " . $data);
		 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization:Bearer ' . $accessToken]);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifyPeer);// this should be set to true in PROD
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$responseData = curl_exec($ch);
		
		if(curl_errno($ch)) 
		{
			//Log::datafast("DATAFAST = curl_error_checkout " . curl_error($ch));
		}
		
		curl_close($ch);

       

		$answer = json_decode($responseData,true);
        dd($answer);
		return $answer['id'];
	}

	public function processResponse(Request $request)
	{
		$dfPack = $request->all;

		$dfResult = json_decode(request($dfPack),true);

		echo "<pre>";
		print_r($dfResult);
		echo "</pre>";

		dd();

		if(isset($dfResult['registrationId']))
		{
			$savedData = $_SESSION['transaction_data'];
			$savedData['datafast'] = [];

			$savedData['datafast']['datafast_token'] = $dfResult['registrationId'];
			$savedData['datafast']['datafast_result'] = $dfResult['result']['code'];
			$savedData['datafast']['datafast_payid'] = $dfResult['id'];
			
			//writeLogDatafast("DATAFAST = send_to_cake " . json_encode($savedData));

			$result = process_transaction_for_crm($savedData);

			$result = json_decode($result,true);

			if(isset($result['status']) && $result['status'] == 1 || $result['status'] == 3)
				sendFormForPayments($result['status']);	
			else
			{
				//$datafast = new Datafast();
				//$datafast->notifyProblem('error del lado de cake datafast al pedir transaccion');
			}
		}
		else
		{
			//do nothing, va a quedar en procesando.
		}
	}

	private function requestDatafast($dfPack) 
	{
		$resourcePath = $dfPack['resourcePath'];
		
		//writeLogDatafast("DATAFAST = request_transaction_result_pre " . $_GET['resourcePath'] . ' ' . $_GET['id']);
		
		//$domain = return_domain();
		$isDebug = env('APP_DEBUG');
		
		if($isDebug)
		{
			$url = "https://eu-test.oppwa.com".$resourcePath;
			$verifyPeer = false;
			$entityId = '8ac7a4c7803f575f018042ab82fb0916';
			$accessToken = 'OGE4Mjk0MTg1YTY1YmY1ZTAxNWE2YzhjNzI4YzBkOTV8YmZxR3F3UTMyWA==';
		}
		else
		{
			$url = "https://eu-prod.oppwa.com".$resourcePath;
			$verifyPeer = true;
			$entityId = '8acda4c881aeabd90181b020482a0fdc';
			$accessToken = 'OGFjZGE0Yzg4MWFlYWJkOTAxODFiMDFmYjM4MDBmYzN8QjlRYzlUTnJjWg==';
		}
		
		
		$url .= "?entityId=" . $entityId;
		
		//echo $url;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Authorization:Bearer ' . $accessToken));
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifyPeer);// this should be set to true in production
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		$responseData = curl_exec($ch);
		
		//respuesta de ellos
		//writeLogDatafast("DATAFAST = request_transaction_result_response " . json_encode($responseData));
		
		if(curl_errno($ch)) 
		{
			//$datafast = new Datafast();
			//$datafast->notifyProblem('curl error al querer traer resultado' . $_GET['resourcePath'] . ' ' . $_GET['id']);
			//writeLogDatafast('curl error al querer traer resultado' . $_GET['resourcePath'] . ' ' . $_GET['id']);
		}
		
		curl_close($ch);
		
		return $responseData;
	}

    private function shortMailHash($mail)
	{
		$hash = hash('md5',$mail);
		
		return(substr($mail,0,3).substr($hash,0,13));
	}

	private function filterDocId($doc)
	{
		if(strlen($doc) > 10)
			return(substr($doc,0,10));
		else if(strlen($doc) < 10)
			return(str_repeat('0',10 - strlen($doc)) . $doc);
		else
			return($doc);
	}
	
	private function filterStreet($FORM)
	{
		return($FORM['datosadicionales']['direccionalumno']);
	}
	
	private function prepareProducts($products)
	{
		$answer = '';
		
		$count = 0;
				
		foreach($products as $p)
		{
			$answer = $answer . 'cart.items['.$count.'].name='.$p['name'];
			$answer = $answer . '&cart.items['.$count.'].description= Oceano Medicina';
			$answer = $answer . '&cart.items['.$count.'].price='.$p['price_usd'];
			$answer = $answer . '&cart.items['.$count.'].quantity='.$p['quantity'];
			$answer = $answer . '&';
			$count++;
		}
		
		return($answer);
	}

	private function formatAmount($val)
	{
		$answer = $val;
		
		if($val == floor($val))
		{
			$val = strval($val);
			$val = $val . '.00';
			$answer = $val;
		}
		else
		{
			$answer = strval($val);
		}
		
		return($answer);
	}

    

}
