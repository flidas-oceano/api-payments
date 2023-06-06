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

class GeneratorController extends Controller
{

	public function processEvents($key = null)
	{
		$this->render(false);
		
		if($key == '939125D442DC324F811AF7')
		{
			$this->loadComponent('Util');
			$this->loadComponent('Mercadopago');
			$this->loadComponent('NotificationHandler');
			
			$this->loadModel('Notif');	
			$evTable = TableRegistry::get('Notif');
			
			$twoMinBefore = $this->Util->getMinutesBefore('2');
			
			$tempay = [];

			//trae los eventos que desde el momento de su creación, ya tengan 2 minutos de vida
			//esto es por dos razones
			//1) en una suscripcion, primero se transacciona y luego se hace la suscri (en ese tramito de tiempo han llegado webhooks
			//y la suscri aun no estaba en la DB)
			//2) Zoho tiene un delay para poder pedirle data de un nuevo contrato por algo que no sea ID
			
			$events = $evTable->find('all',array('conditions' => array('status' => 'pending', 'Notif.moment <=' => $twoMinBefore)))->limit(5)->toArray();		
				
			//--- HOLD OUT
			foreach($events as $ev)
			{		
				//le pasamos el evento (o sea el webhook original) como objeto
				$webhook_event = new \stdClass();
				$webhook_event->data = new \stdClass();
				$webhook_event->data->id = $ev->event_id;
				$webhook_event->type = $ev->type;
				
				$ev->status = 'working';
				
				//add SO
				$tempay = $this->Mercadopago->retrieveEvent($webhook_event, $ev->country);
				$ev->so = isset($tempay["response"]['external_reference']) ? $tempay["response"]['external_reference'] : '';
			}

			$evTable->saveMany($events);
			//----
			
			foreach($events as $ev)
			{	
				//le pasamos el evento (o sea el webhook original) como objeto
				$webhook_event = new \stdClass();
				$webhook_event->data = new \stdClass();
				$webhook_event->data->id = $ev->event_id;
				$webhook_event->type = $ev->type;
			
				$duplicated = false;
				$ignore = false;
				
				if (!is_null($webhook_event->type))
				{
					$payment = $this->Mercadopago->retrieveEvent($webhook_event, $ev->country);
					
					//primero vemos que no esté repetido (procesado y ok)
					if($this->NotificationHandler->alreadyProcessed($ev->event_id))
						$duplicated = true;


					if(isset($payment['response']['external_reference']))
					{
						$so = $payment['response']['external_reference'];
						
						if($so[0] == 'x') //es creado por mi
						{
							
						}
						else
							$ignore = true;
					}
					else
					{
						$ignore = true;
					}

					if(!$duplicated && !$ignore)
					{
						
						$this->payment = $payment;
						
						$result = $this->validatePayment($payment, $ev->country);
									
						//si dió bien... actualiza estado
						if($result)
						{
							
							if($this->NotificationHandler->updateEvent($ev->id, 'success'))
							{
								//$this->log('SUCCESS'.$ev->id);
							}
							else
							{
								$this->log('no puedo actualizar estado success de evento id = ' . $ev->id);
							}	
								
						}
					}
					else if ($duplicated)
					{
						if(!$this->NotificationHandler->updateEvent($ev->id, 'duplicated'))
							$this->log('no puedo actualizar estado dupli de evento id = ' . $ev->id);
					}
					else if ($ignore)
					{
						$this->NotificationHandler->updateEvent($ev->id, 'ignore');	
					}
				}
				else
				{
					$this->NotificationHandler->updateEvent($ev->id, 'ignore');	
				}
				
				
			}
			
			
			
		}
		else
		{
			throw new NotFoundException(__('Missing function'));
		}
	}
	
	private function removeX($so)
	{
		$answer = $so;
		
		if($so[0] == 'x')
			$answer = substr($so, 1);
		
		return($answer);
	}

	public function validatePayment($payment,$country)
	{

		$answer = false;
		
		$zohoPart = false; //anota si pudo hacer la parte de actualizar lo que corr
		//esponda en zoho... o no.
		$zohoPart2 = true;
		$zohoPart3 = true;
		$zohoPart4 = true;
		
		$isSub = false;
		
		$this->loadComponent('Util');
		$this->loadComponent('NewZoho');
		

		if(!empty($payment) && isset($payment["response"]['external_reference']))
		{
			$so = $payment["response"]['external_reference'];
			$so = $this->removeX($so);

			$zoho_sale = $this->NewZoho->fetchRecordWithValue('Sales_Orders','SO_Number',$so, true);
		
			$this->writeLogs('A');
			
			$shouldWriteZoho = false;
			$firstPayment = false;
			
			//se fija que sea pago primer mes && y aprobado
			if(isset($payment['response']['point_of_interaction']))
			{
				if($payment['response']['status'] == 'approved')
				{
					if(isset($payment['response']['point_of_interaction']['transaction_data']))
					{
						if(isset($payment['response']['point_of_interaction']['transaction_data']['invoice_period']))
						{
							if($payment['response']['point_of_interaction']['transaction_data']['invoice_period']['period'] == 1)
							{
								$shouldWriteZoho = true;
								$firstPayment = true;
								$this->firstPayment = true;
							}
						}
						
						if($payment['response']['point_of_interaction']['transaction_data']['subscription_id'] != null)
						{
							$isSub = true;
							$update_mode = $this->NewZoho->updateRecord('Sales_Orders', array(
							'mp_subscription_id' => $payment['response']['point_of_interaction']['transaction_data']['subscription_id']),
							$zoho_sale->getEntityId());
							
							if($update_mode['result'] == 'error')
							{
								$zohoPart2 = false;
							}
							
							//OTRAS ACTUALIZACIONES
							$zohoPart3 = $this->updateDatosFacturacion($zoho_sale->getEntityId());
							$zohoPart4 = $this->updateDatosCuotas($zoho_sale->getEntityId());
							
							
							
						}
						else
							$isSub = false;
						
							
					}
				}
			}
			
			if(!$this->Util->has($so,'cobranzas') && $shouldWriteZoho)
			{
	
				$this->writeLogs('B');
				
				
				
				if($payment['response']['status'] == 'approved')
				{
					$this->writeLogs('C');

					$this->markZoho($so);

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
		
			if($zohoPart && $zohoPart2 && $zohoPart3 && $zohoPart4) // esto significa: ok, ya pude hacer las actualizaciones en zoho! (o no, si había que omitirlas)
			{


				//notificar al usuario y oceano (mail)
				if($country == 'mx_msk')
				{
					$updatemp = $this->NewZoho->updateRecord('Sales_Orders', array(
						'respuesta_mp'=> $payment['response']['status']),
						$zoho_sale->getEntityId());
				}
				else
					$this->notify($payment, $country, $firstPayment, $isSub);
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
	
	private function updateDatosCuotas($sale_id)
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
			
		}
		
		if(!empty($purchase))
		{
			
			$data = json_decode($purchase->data,true);
			
			$cuotas = intval($data['months']);
			$total = floatval($data['amount']);
			$monto = round($total/$cuotas);
		
			//echo $monto - number_format($monto/3,2);
			//die();
		
			$update = $this->NewZoho->updateRecord('Sales_Orders', array(
			'Monto_de_Anticipo'=> $monto,
			'Monto_de_Saldo'=> strval($total - $monto),
			'Anticipo'=> strval($monto),
			'Cantidad'=> $cuotas, //Nro de cuotas
			'Valor_Cuota'=> $monto, //Costo de cada cuota
			'Cuotas_restantes_sin_anticipo'=> $cuotas - 1,
			'Fecha_de_Vto'=> date('Y-m-d'),
			'Modalidad_de_pago_del_Anticipo'=> 'Mercado pago (Vs)',
			'Medio_de_Pago'=> 'Mercadopago (Vs)'),
			$sale_id);
			
			if($update['result'] == 'ok')
				$answer = true;
		}
		
		return($answer);
		
	}

	public function updateX($country)
	{
		$this->render(false); 
		
		$this->loadComponent('Mercadopago');
		$this->loadComponent('Util');
		$this->loadComponent('NotificationHandler');

		$event = $this->Util->takeIPN(); 
		
		//guardo el evento para procesar luego
		$save = $this->NotificationHandler->saveEvent($event, $country);
		
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


}