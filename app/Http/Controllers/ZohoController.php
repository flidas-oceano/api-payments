<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateContractZohoRequest;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use zcrmsdk\crm\crud\ZCRMInventoryLineItem;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use zcrmsdk\oauth\ZohoOAuth;
use zcrmsdk\crm\crud\ZCRMRecord;
use zcrmsdk\crm\exception\ZCRMException;

class ZohoController extends Controller
{

    public $emi_owner;

    public function __construct()
    {
        try {
            $this->emi_owner = '2712674000000899001';

            ZCRMRestClient::initialize([
                "client_id" => env('APP_DEBUG') ? env('ZOHO_API_PAYMENTS_TEST_CLIENT_ID') : env('ZOHO_API_PAYMENTS_PROD_CLIENT_ID'),
                "client_secret" => env('APP_DEBUG') ? env('ZOHO_API_PAYMENTS_TEST_CLIENT_SECRECT') : env('ZOHO_API_PAYMENTS_PROD_CLIENT_SECRECT'),
                "redirect_uri" => env('APP_DEBUG') ? 'https://www.zoho.com' : 'https://www.oceanomedicina.com.ar',
                "token_persistence_path" => Storage::path("zoho"),
                "persistence_handler_class" => "ZohoOAuthPersistenceByFile",
                "currentUserEmail" => env('APP_DEBUG') ? 'copyzoho.custom@gmail.com' : 'sistemas@oceano.com.ar', //'copyzoho.custom@gmail.com',
                "accounts_url" => 'https://accounts.zoho.com',
                "access_type" => "offline"
            ]);

            $oAuthClient = ZohoOAuth::getClientInstance();
           $refreshToken = env('APP_DEBUG') ? env('ZOHO_API_PAYMENTS_TEST_REFRESH_TOKEN') : env('ZOHO_API_PAYMENTS_PROD_REFRESH_TOKEN');
           $userIdentifier = env('APP_DEBUG') ? 'copyzoho.custom@gmail.com' : 'sistemas@oceano.com.ar';
           $oAuthTokens = $oAuthClient->generateAccessTokenFromRefreshToken($refreshToken, $userIdentifier);
       }catch(Exception $e){
            //dd($e);
        }
    }

    public function fetchRecordWithValue($module, $field, $value)
    {
        $answer = 'error';
        $record = null;
        try {
            $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance($module);  //To get module instance
            $response = $moduleIns->searchRecordsByCriteria('(' . $field . ':equals:' . $value . ')');
            $records = $response->getData();  //To get response data
            $answer = $records[0];
        } catch (\Exception $e) {
           // dump($e);
        }
        return ($answer);
    }

    public function getContractBySO(Request $request, $so)
    {
        $answer = 'error';

        $so = (int)$so;
        $record = null;

        try {
            $record = $this->fetchRecordWithValue('Sales_Orders', 'SO_Number', $so);
            if ($record != 'error') {
                $answer = $record;
            } else
                $answer = '???';
        } catch (\Exception $e) {
           // dump($e);
        }

        return response()->json($answer);
    }

    //trae records en base a condiciones
    private function fetchRecords($module, $conditions, $log = false)
    {
        $answer = array();
        try {
            $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance($module);  //To get module instance
            $response = $moduleIns->searchRecordsByCriteria($conditions);
            $records = $response->getData();  //To get response data

            $answer = $records;
        } catch (\Exception $e) {
            if ($log) {
               // $this->log($e);
            }
        }

        return ($answer);
    }

    //crea un nuevo record, el que vos quieras, contacto, contrato...
    //pero momento! si ya existe no crea nada nuevo.
    //en cualquier caso, te devuelve el id
    //exception -> reventó todo
    //ok -> salio bien
    //duplicate -> no es malo, pero está duplicado, o sea que no se crea, sino que trae su id
    private function createNewRecord($type, $data)
    {
        $status = 'ok'; //el status, y en base a esto armo el answer o no...
        //ok = salio bien, y te paso el id
        //exception = exploto todo

        $answer = array();

        $answer['result'] = '';
        $answer['id'] = '';


        //hace el intento de subir el record
        try {
            $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance($type); //to get the instance of the module

            $record = ZCRMRecord::getInstance($type, null);

            foreach ($data as $k => $v)
                $record->setFieldValue($k, $v);

            $responseIn = $record->create();
            $details = $responseIn->getDetails();

            $answer['result'] = 'ok';
            $answer['id'] = $details['id'];
        } catch (ZCRMException $e) {
            $handle = $this->handleError($e, $type, $data);

            if ($handle != 'error') {
                $answer['result'] = 'duplicate';
                $answer['id'] = $handle;
            } else {
                $answer['result'] = 'error';
                dd($e);
            }
        }

        return ($answer);
    }


    //gestiona un error de subida de record
    //error es el codigo y el mensaje
    //type para saber qué estaba subiendo
    //data que estaba subiendo
    //respuesta ok = te da un id, sino error
    private function handleError($error, $type, $data)
    {
        $answer = 'error';

        $details = $error->getExceptionDetails();
        $cod = $error->getExceptionCode();

        if ($cod == 'DUPLICATE_DATA') {
            $answer = $details['id'];
        }


        return ($answer);
    }

    //actualiza un record, le pasas el id separado
    private function updateRecord($type, $data, $id, $workflow = true)
    {
        $answer = array();

        $answer['result'] = 'error';
        $answer['id'] = '';

        try {
            $zcrmRecordIns = ZCRMRecord::getInstance($type, $id);

            foreach ($data as $k => $v)
                $zcrmRecordIns->setFieldValue($k, $v);

            //workflow?
            if ($workflow)
                $apiResponse = $zcrmRecordIns->update();
            else
                $apiResponse = $zcrmRecordIns->update(array());

            if ($apiResponse->getCode() == 'SUCCESS') {
                $answer['result'] = 'ok';
                $answer['id'] = $id;
            }
        } catch (\Exception $e) {
            //$this->log(print_r($e, true));
        }

        return ($answer);
    }

    public function updateZohoStripe(UpdateContractZohoRequest $request)
    {

        $dataUpdate = [
            'Email' => $request->email,
            'Monto_de_Anticipo' => $request->installment_amount,
            'Monto_de_Saldo' => $request->amount - $request->installment_amount,
            'Cantidad' => $request->installments, //Nro de cuotas
            'Valor_Cuota' => $request->installment_amount, //Costo de cada cuota
            'Cuotas_restantes_sin_anticipo' => $request->installments - 1,
            'Fecha_de_Vto' => date('Y-m-d'),
            'Status' => 'Contrato Efectivo',
            'Modalidad_de_pago_del_Anticipo' => 'Stripe',
            'Medio_de_Pago' => 'Stripe',
            'Es_Suscri' => boolval($request->is_suscri),
            'stripe_subscription_id' => $request->subscriptionId,
            'L_nea_nica_6' => $request->fullname,
            'Billing_Street' => $request->address,
            'L_nea_nica_3' => strval($request->dni),
            'Tel_fono_Facturacion' => $request->phone
        ];

        $updateContract = $this->updateRecord('Sales_Orders', $dataUpdate, $request->contractId, true);


        return response()->json($updateContract);
    }

    public function obtainData(UpdateContractZohoRequest $request)
    {

        $dataUpdate = [
            'Email' => $request->email,
            'Monto_de_Anticipo' => $request->installment_amount,
            'Monto_de_Saldo' => $request->amount - $request->installment_amount,
            'Cantidad' => $request->installments, //Nro de cuotas
            'Valor_Cuota' => $request->installment_amount, //Costo de cada cuota
            'Cuotas_restantes_sin_anticipo' => $request->installments - 1,
            'Fecha_de_Vto' => date('Y-m-d'),
            'Status' => 'Contrato Efectivo',
            'Modalidad_de_pago_del_Anticipo' => 'Stripe',
            'Medio_de_Pago' => 'Stripe',
            'Es_Suscri' => boolval($request->is_suscri),
            'stripe_subscription_id' => $request->subscriptionId,
            'L_nea_nica_6' => $request->fullname,
            'Billing_Street' => $request->address,
            'L_nea_nica_3' => strval($request->dni),
            'Tel_fono_Facturacion' => $request->phone
        ];

        $updateContract = $this->updateRecord('Sales_Orders', $dataUpdate, $request->contractId, true);


        return response()->json($updateContract);
    }

    public function createLead(Request $request)
    {
        $data = $request->all();
        // $dataJson = json_decode($request->input('dataJson'), true);

        //Buscar en db por id, traer el lead
        //Craer lead en crm, traer el id_lead generado en crm y meterlo en el lead de ventapresencial, en el campo entity_id_crm

        $leadData = $this->processLeadData($data);

        $leadIsDuplicate = $this->updateFetchDuplicateLeads($leadData['Email']);

        if ($leadIsDuplicate)
            $leadData['Lead_Duplicado'] = true;

        $newLead =  $this->createNewRecord('Leads', $leadData);

        return response()->json($newLead);
    }

    public function createContact(Request $request)
    {
        $data = $request->all();

        //lo primero que haremos es intentar crear el contacto
        $contactData = array(
            'First_Name' => $data['name'],
            'Last_Name' => $data['surname'],
            'Email' => $data['email'],
            'DNI' => $data['dni'],
            'Home_Phone' => $data['phone'],
            'Pais' => $data['country'],
        );

		$newContact = $this->createNewRecord('Contacts',$contactData);

        return (json_encode($newContact));
    }

    public function createAddress(Request $request)
    {
        $data = $request->all();

        //armamos data de la dire y la creamos
			$addressData = array(
				'Calle' => $data['street'],
				'C_digo_Postal' => $data['postalcode'],
				'Name' => 'direccion',
				'Contacto' => $data['contact_id'],
				'Provincia' => $data['province'],
				'Pais' => $data['country'],
				'Tipo_Dom' => $data['address_type']
			);

			//primero vamos a ver si existe una dirección con el mismo ID de contacto
			//para no repetir
			$existAddress = $this->fetchRecordWithValue('Domicilios', 'Contacto', $data['contact_id']);

			//esto significa que no existe
			if($existAddress == 'error'){
				$newAddress = $this->createNewRecord('Domicilios',$addressData);
			}else //en cambio, si existe, actualizo
			{
				$newAddress = $this->updateRecord('Domicilios',$addressData,$existAddress->getEntityId());
			}

			return(json_encode($newAddress));
    }

    public function createSale(Request $request)
    {
        $data = $request->all();

         //armo el product details en base a las cosas que compró el usuario...
        $productDetails = $this->buildProductDetails($data['products']);

        if($productDetails != 'error')
        {
            $saleData = array(
                'Subject' => 'etc',
                'Status' => 'Contrato Pendiente',
                'Contact_Name' => $data['contact_id'],
                'Cantidad' => $data['installments'],
                'Fecha_de_Vto' => date('Y-m-d'),
                'L_nea_nica_6' => $data['name'],
                'L_nea_nica_3' => $data['identification'],
                'Billing_Street' => $data['address'],
                'Tipo_De_Pago' => $data['payment_type'],
                '[products]' => $productDetails,
                'Pais' => $data['country'],
                'Es_Suscri' => $data['is_sub'],
                'Anticipo' => strval($data['payment_in_advance']),
                'Cuotas_restantes_sin_anticipo' => $data['left_installments'],
                'Medio_de_Pago' => $data['left_payment_type'],
                'Cuotas_totales' => 1,
                'Currency' => $data['currency'],
                'Modalidad_de_pago_del_Anticipo' => $data['left_payment_type'],
                'Tipo_IVA' => 'Consumidor Final - ICF',
            );

            $newSale = $this->createRecordSale($saleData);

            return(json_encode($newSale));
        }
        else
        {
            $answer['id'] = '';
            $answer['result'] = 'error';

            return(json_encode(['result' => 'error', 'detail' => 'issue with products']));
        }


    }

    private function createRecordSale($data)
    {
        $answer = array();

        $answer['id'] = '';
        $answer['result'] = 'error';

        try {
            $record = ZCRMRestClient::getInstance()->getRecordInstance("Sales_Orders", null); // To get record instance
            //campos sales orders
            foreach ($data as $k => $v) {
                if ($k != '[products]')
                    $record->setFieldValue($k, $v);
            }

            //productos
            foreach ($data['[products]'] as $p) {
                $product = ZCRMInventoryLineItem::getInstance(null); // To get ZCRMInventoryLineItem instance

                $product->setListPrice($p['List Price']);
                $product->setProduct(ZCRMRecord::getInstance("Products", $p['Product Id']));
                $product->setQuantity($p['Quantity']);

                if ($p['Discount'] > 0)
                    $product->setDiscountPercentage($p['Discount']);

                $record->addLineItem($product);
            }

            $responseIns = $record->create();

            if ($responseIns->getHttpStatusCode() == 201)
            {
                $answer['result'] = 'ok';

                $aux = $responseIns->getDetails();

                $answer['id'] = $aux['id'];
            }
        } catch (\Exception $e) {

        }

        return ($answer);
    }

    //arma el detalle de productos para el contrato
    private function buildProductDetails($products)
    {
		$answer = array();
		$nonexistent = false;
		//arma y reemplaza sku por ID de producto en zoho
		foreach($products as $k => $p)
        {
            $answer[] = array(
                'Product Id' => $k,
                'Quantity' => (int)$p['quantity'],
                'List Price' => (float)$p['price'],
                //'List Price #USD' => (float)$p['price_usd'],
                //'List Price #Local Currency' => (float)$p['price'],
                'Discount' => (float)$p['discount']
            );
		}


		return($answer);
	}

    public function convertLead(Request $request)
    {
        $data = $request->all();
        $leadId = $data['id'];

        $response = $this->convertRecord($leadId,'Leads');

        return(json_encode($response));
    }

    private function convertRecord($id, $type)
    {
        $answer['result'] = 'error';
        $answer['id'] = '';
        $answer['detail'] = '';

        try
        {
            $record = ZCRMRestClient::getInstance()->getRecordInstance($type, $id); // To get record instance


            $contact = ZCRMRecord::getInstance("Contacts", Null); // to get the record of deal in form of ZCRMRecord insatnce
            $details = array("overwrite"=>TRUE);

            $responseIn = $record->convert($contact, $details); // to convert record

            $answer['result'] = 'ok';
            $answer['id'] = $responseIn["Contacts"];

        } catch (\Exception $e)
        {
            $answer['detail'] = $e->getMessage();
        }

        return($answer);
    }

    private function processLeadData($data)
    {
        //hay contactos?
        if ($this->fetchRecordWithValue('Contacts', 'Email', $data["email"]) == "error") {
            $leadData['Es_Contacto'] = false;
        } else {
            $leadData['Es_Contacto'] = true;
        }
        
        $leadData['First_Name']             = $data["name"];
        $leadData['Last_Name']              = $data["username"];
        $leadData['Phone']                  = $data["telephone"];
        $leadData['Email']                  = $data["email"];
        $LeadHistoricoData['Fuente_de_Lead'] = array(0 => $data['lead_source'] ?? 'Venta Presencial');//hay que definir donde buscamos el dato 
        $LeadHistoricoData['FUENTE']         = $data['source'] ?? 'Venta Presencial';//hay que definir donde buscamos el dato
        $leadData['Lead_Status']            = $data['status']?? 'Contacto urgente';
        $leadData['Pais']                   = $data["country"];
        $leadData['pp']                     = $data["profession"];
        $leadData['Especialidad']           = [$data["speciality"]];
        $leadData['*owner']                 = $this->emi_owner;

        return $leadData;
    }

    private function updateFetchDuplicateLeads($mail)
    {
        //hay leads con ese mail?
        $searchBy              =  "((Email:equals:" . $mail . ")and(Lead_Status:equals:Contacto urgente))";
        $sameUserLeads       =  $this->fetchRecords('Leads', $searchBy); //<-- busca records para saber si el usuario ya intentó comprar anteriormente

        if (count($sameUserLeads) == 0) {
            //no encontró nada, entonces no tiene que actualizar y no hay duplicados
            return false;
        }

        //si llegó acá es porque hay > 0 leads con mismo mail
        //vamos a actualizar SÓLO UNO de ellos qe tenga lead_duplicado = false

        $leadK = -1;

        foreach ($sameUserLeads as $k => $s) {
            if (!$s->getFieldValue('Lead_Duplicado')) {
                $leadK = $k;
                break;
            }
        }

        //o sea, que uno de ellos tiene lead_duplicado = false -> lo actualizamos
        if ($leadK != -1)
            $this->updateRecord('Leads', array('Lead_Duplicado' => true), $sameUserLeads[$leadK]->getEntityId(), false);

        return true;
    }

    public function getProducts(){
        try {
            $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance('Products');  //To get module instance
            $records = $moduleIns->getRecords();
            $products = [];

            foreach($records->getData() as $product){
                $products[] = [
                        "name" => $product->getFieldValue('Product_Name'),
                        "code" => $product->getFieldValue('Product_Code'),
                        "price" => $product->getFieldValue('Unit_Price'),
                        "category" => $product->getFieldValue('Product_Category'),
                        "active" => $product->getFieldValue('Product_Active'),
                ];
            }

            return response()->json($products);
        } catch (\Exception $e) {
            dd($e);
        }
    }
}
