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

class WebhooksController extends Controller
{


    public $Mercadopago, $Util, $NewZoho;

	public function __construct()
    {
        $this->Mercadopago = App::make('App\Http\Controllers\MercadopagoController');
        $this->Util = App::make('App\Http\Controllers\UtilController');
        $this->NewZoho = App::make('App\Http\Controllers\ZohoController');
	}

	public function processEvents($key = null)
	{
		$this->render(false);
		
		if($key == '939125D442DC324F811AF7')
		{
			$this->loadComponent('Util');
			$this->loadComponent('Mercadopago');
			$this->loadComponent('EventHandler');
			
			$this->loadModel('EventsMerca');	
			$evTable = TableRegistry::get('EventsMerca');
			
			$twoMinBefore = $this->Util->getMinutesBefore('2');

			//trae los eventos que desde el momento de su creación, ya tengan 2 minutos de vida
			//esto es por dos razones
			//1) en una suscripcion, primero se transacciona y luego se hace la suscri (en ese tramito de tiempo han llegado webhooks
			//y la suscri aun no estaba en la DB)
			//2) Zoho tiene un delay para poder pedirle data de un nuevo contrato por algo que no sea ID
			
			$events = $evTable->find('all',array('conditions' => array('status' => 'pending', 'EventsMerca.moment <=' => $twoMinBefore)))->limit(5)->toArray();		
			
			//--- HOLD OUT
			foreach($events as $ev)
				$ev->status = 'working';

			$evTable->saveMany($events);
			//----
			
			foreach($events as $ev)
			{
								
				$this->log('id is' . $ev->id);
				
				$duplicated = false;
				
				//primero vemos que no esté repetido (procesado y ok)
				if($this->EventHandler->alreadyProcessed($ev->event_id))
					$duplicated = true;
				
				if(!$duplicated)
				{
					
					//le pasamos el evento (o sea el webhook original) como objeto
					$webhook_event = new \stdClass();
					$webhook_event->data = new \stdClass();
					$webhook_event->data->id = $ev->event_id;
					$webhook_event->type = $ev->type;
					
					$payment = $this->Mercadopago->retrieveEvent($webhook_event, $ev->country);
					$result = $this->validatePayment($payment, $ev->country);
								
					//si dió bien... actualiza estado
					if($result)
					{
						
						if($this->EventHandler->updateEvent($ev->id, 'success'))
						{
							//$this->log('SUCCESS'.$ev->id);
						}
						else
						{
							$this->log('no puedo actualizar estado success de evento id = ' . $ev->id);
						}	
							
					}
				}
				else
				{
					if(!$this->EventHandler->updateEvent($ev->id, 'duplicated'))
						$this->log('no puedo actualizar estado dupli de evento id = ' . $ev->id);
				}
				
				
			}

		}
		else
		{
			throw new NotFoundException(__('Missing function'));
		}
	}
	public function validatePayment($payment,$country)
	{

		$answer = false;

		$zohoPart = false; //anota si pudo hacer la parte de actualizar lo que corr
		//esponda en zoho... o no.
		
		$zohoPart2 = true;
		$zohoPart3 = true;
		
		$isSub = false;
		
		$this->loadComponent('Util');
		$this->loadComponent('NewZoho');
			
		if(!empty($payment) && isset($payment["response"]['external_reference']))
		{
			$so = $payment["response"]['external_reference'];
			
			$this->writeLogs('A');
			
			if(!$this->Util->has($so,'cobranzas') && $this->shouldWriteZohoStatus($so))
			{
				$zoho_sale = $this->NewZoho->fetchRecordWithValue('Sales_Orders','SO_Number',$so, true);

				$this->writeLogs('B');
				
				
				if($payment['response']['status'] == 'approved')
				{
					$this->writeLogs('C');
					
					//si es suscri, marca como es suscri
					if($this->isSub($so))
					{
						$this->writeLogs('D');
						$isSub = true;
						$this->markZoho($so);
					}
					
					$this->writeLogs('E');
							
					//caso particular... argentina televenta
					if($country == 'ar' && !$this->isEcom($so))
					{
						
						//sólo lo actualizo si no tiene valor = contrato efectivo
						$saleStatus = $this->NewZoho->fetchContractData($so, 'Status');
						
						$this->writeLogs('F');
						
						if($saleStatus != 'Contrato Efectivo')
							$zohoPart = $this->updateContract($so,'Contrato para Auditoría');
						else
							$zohoPart = true;
					}
					else
						$zohoPart = $this->updateContract($so,'Contrato Efectivo');
					
					if($payment["response"]['metadata']['payment_process'] == p_SUPER)
					{
						
						
						$cuotas = intval($payment['response']['installments']);
						$monto = floatval($payment['response']['installments']);
						
						$update = $this->NewZoho->updateRecord('Sales_Orders', array(
						'Monto_de_Anticipo'=> $monto,
						'Monto_de_Saldo'=> strval($monto - round($monto/$cuotas)),
						'Anticipo'=> strval($monto - round($monto/$cuotas)),
						'Cantidad'=> $cuotas, //Nro de cuotas
						'Valor_Cuota'=> $monto, //Costo de cada cuota
						'Cuotas_restantes_sin_anticipo'=> $cuotas - 1,
						'Fecha_de_Vto'=> date('Y-m-d'),
						'Modalidad_de_pago_del_Anticipo'=> 'Mercado pago (Vs)',
						'Medio_de_Pago'=> 'Mercadopago (Vs)'),
						$zoho_sale->getEntityId());
						
						if($update['result'] != 'ok')
							$zohoPart2 = false;
						
						//actualización de otros datos
						$zohoPart3 = $this->updateDatosFacturacion($zoho_sale->getEntityId());
					
					}
	
					
				}
				else if ($payment['response']['status'] == 'rejected')
				{				
					/*					
						(X) rejected = pasarela 
						(X) rejected = ecom trad
						(X) rejected = ecom sus
						(X) progress -> rejected = pasarela sus
						(X) progress -> rejected = pasarela trad
						(X) progress -> rejected = ecom trad
						(X) progress -> rejected = ecom sus
					*/
			
					$this->writeLogs('G');
			
					//si es suscri, dado que es primer cobro pending to rejected desactiva la suscri
					if($this->isSub($so))
					{
						$this->log('will go and deactivate sub');
						$this->writeLogs('deact');
						$this->deactivateSub($so);
					}
					
					if($this->isEcom($so))
					{
						//el contrato estará borrado  (si fue directo rejected, sino si fue progress -> rejected... pues no)
						
						$saleStatus = $this->NewZoho->fetchContractData($so, 'Status');
						
						if($saleStatus != 'error')
						{
							//si existe, es el edge case prog -> rej
							
							if($saleStatus != 'Contrato Efectivo' && $saleStatus != 'Contrato para Auditoría')
								$zohoPart = $this->updateContract($so,'Pago rechazado');
							else
								$zohoPart = true;
						}
						else
							$zohoPart = true;
					}
					else
					{
						//sólo lo actualizo si no tiene valor = contrato efectivo
						$saleStatus = $this->NewZoho->fetchContractData($so, 'Status');
						
						$this->writeLogs('H');
						
						if($saleStatus != 'Contrato Efectivo' && $saleStatus != 'Contrato para Auditoría')
							$zohoPart = $this->updateContract($so,'Pago rechazado');
						else
							$zohoPart = true;
					}
				}
				else //la otra alternativa suele ser "en proceso" (nobody cares about that)
				{
					$this->writeLogs('I');
					$zohoPart = true;
				}
			}
			else
			{
				$this->writeLogs('K');
				$zohoPart = true;
			}
		
			
			if($zohoPart && $zohoPart2 && $zohoPart3) // esto significa: ok, ya pude hacer las actualizaciones en zoho! (o no, si había que omitirlas)
			{

				//notificar al usuario y oceano (mail)
				if($country == 'mx_msk')
				{
					$updatemp = $this->NewZoho->updateRecord('Sales_Orders', array(
						'respuesta_mp'=> $payment['response']['status']),
						$zoho_sale->getEntityId());
				}
				else
					$this->notify($payment, $country);
			}

		}

		
		$answer = $zohoPart;

		return($answer);
	}

	private function updateDatosFacturacion($sale_id)
	{
		$answer = false;
		
		$this->loadComponent('NewZoho');
		$ecomauxTable = TableRegistry::get('Pasarelaux');
		
		$purchase = [];
		
		try
		{
			$purchase = $ecomauxTable->get($sale_id);	
		}
		catch(\Exception $e)
		{
			//esto significa que no existe...
		}
		
		if(!empty($purchase))
		{
			$data = json_decode($purchase->data,true);
			
			$dataUpdate = [
				'L_nea_nica_6' => $data['fullname'],
				'Billing_Street' => $data['address'],
				'L_nea_nica_3' => $data['dni'],
				'Tel_fono_Facturacion' => $data['phone'],
				'Email' => $data['mail']
			];
			
			$update = $this->NewZoho->updateRecord('Sales_Orders', $dataUpdate, $sale_id);
			
			if($update['result'] == 'ok')
				$answer = true;
			
		}
		
		
			
		return($answer);
	}


	public function updateX($country)
	{
		
		$event = $this->Util->takeWebhook(); 

		Log::info('vino evento',$event);
		
		//guardo el evento para procesar luego
		$save = $this->saveEvent($event, $country);
		
		if($save)
		{
			http_response_code(200);
			return;	
		}
		else
		{
			http_response_code(400);
			return;	
		}
	}	
	
	public function updateMxmsk()
	{
		$this->render(false);
		$this->updateX('mx_msk');
		
	}

	//guarda en DB un evento de MP
	public function saveEvent($raw_event, $country)
	{
		$answer = false;
		
		//primero reviso que tenga ALGO
		if(isset($raw_event->data->id) && $raw_event->data->id != null)
		{
			
		}
		else
		{
			return false;
		}
	
		$ev = CronosElements::create([
			'type' => $raw_event->type,
			'moment' => date('Y-m-d G:i:s'),
			'country' => $country,
			'event_id' => $raw_event->data->id,
			'status' => 'pending'
        ]);

		
		if($ev->save())
			$answer = true;
	
		return($answer);
	}


}