<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use zcrmsdk\oauth\ZohoOAuth;
use zcrmsdk\crm\crud\ZCRMRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use zcrmsdk\crm\exception\ZCRMException;
use zcrmsdk\crm\setup\org\ZCRMOrganization;
use zcrmsdk\crm\crud\ZCRMInventoryLineItem;
use zcrmsdk\crm\crud\ZCRMTax;
use App\Http\Requests\UpdateContractZohoRequest;
use zcrmsdk\crm\setup\restclient\ZCRMRestClient;
use App\Models\{Contact, Lead, Profession, PurchaseProgress, Speciality, MethodContact};

class ZohoController extends Controller
{

    public $emi_owner;

    public function reinit()
    {
        try {

            ZCRMRestClient::initialize([
                "client_id" => '1000.3RG4V6380Z6J0QJ8VGXO2V0PBMELGK',
                "client_secret" => '81d8708344811e068588c0bf635a186f195da8bedb',
                "redirect_uri" => 'https://www.msklatam.com',
                "token_persistence_path" => Storage::path("zohomsk"),
                "persistence_handler_class" => "ZohoOAuthPersistenceByFile",
                "currentUserEmail" => 'integraciones@msklatam.com',
                "accounts_url" => 'https://accounts.zoho.com',
                "access_type" => "offline"
            ]);

            $oAuthClient = ZohoOAuth::getClientInstance();

            $refreshToken = "1000.21d634af0695ff7e2ea1c783628d3ead.a5ec4489bb6cdb31c8ac2f5435f94923";
            $userIdentifier = "integraciones@msklatam.com";
            $oAuthTokens = $oAuthClient->generateAccessTokenFromRefreshToken($refreshToken, $userIdentifier);
            //$this->token = $oAuthClient->getAccessToken('https://www.msklatam.com');

        } catch (Exception $e) {
            Log::error($e);

        }
    }

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
                "currentUserEmail" => env('APP_DEBUG') ? 'copyzoho.custom@gmail.com' : 'sistemas@oceano.com.ar',
                //'copyzoho.custom@gmail.com',
                "accounts_url" => 'https://accounts.zoho.com',
                "access_type" => "offline"
            ]);

            $oAuthClient = ZohoOAuth::getClientInstance();
            $refreshToken = env('APP_DEBUG') ? env('ZOHO_API_PAYMENTS_TEST_REFRESH_TOKEN') : env('ZOHO_API_PAYMENTS_PROD_REFRESH_TOKEN');
            $userIdentifier = env('APP_DEBUG') ? 'copyzoho.custom@gmail.com' : 'sistemas@oceano.com.ar';
            $oAuthTokens = $oAuthClient->generateAccessTokenFromRefreshToken($refreshToken, $userIdentifier);
        } catch (Exception $e) {
            Log::error($e);

        }
    }

    public function fetchRecordWithValue($module, $field, $value)
    {
        $answer = 'error';
        $record = null;
        try {
            $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance($module); //To get module instance
            $response = $moduleIns->searchRecordsByCriteria('(' . $field . ':equals:' . $value . ')');
            $records = $response->getData(); //To get response data
            $answer = $records[0];
        } catch (\zcrmsdk\crm\exception\ZCRMException $e) {
            Log::error($e);
        }
        return ($answer);
    }


    public function getContractBySO(Request $request, $so)
    {
        $answer = 'error';

        $so = (int) $so;
        $record = null;

        try {
            $record = $this->fetchRecordWithValue('Sales_Orders', 'SO_Number', $so);
            if ($record != 'error') {
                $answer = $record;
            } else
                $answer = '???';
        } catch (\Exception $e) {
            Log::error($e);
        }

        return response()->json($answer);
    }

    //trae records en base a condiciones
    public function fetchRecords($module, $conditions, $log = false)
    {
        $answer = array();
        try {
            $moduleIns = ZCRMRestClient::getInstance()->getModuleInstance($module); //To get module instance
            $response = $moduleIns->searchRecordsByCriteria($conditions);
            $records = $response->getData(); //To get response data

            $answer = $records;
        } catch (\Exception $e) {
            if ($log) {
                Log::error($e);
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
    public function createNewRecord($type, $data)
    {
        $status = 'ok'; //el status, y en base a esto armo el answer o no...
        //ok = salio bien, y te paso el id
        //exception = exploto todo

        $answer = array();

        $answer['result'] = '';
        $answer['id'] = '';
        $answer['detail'] = '';


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
                $answer['detail'] = $e->getExceptionDetails();
                Log::error($e);
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
        // ID_ALREADY_CONVERTED

        return ($answer);
    }

    //actualiza un record, le pasas el id separado
    private function updateRecord($type, $data, $id, $workflow = true)
    {
        $answer = array();

        $answer['result'] = 'error';
        $answer['id'] = '';
        $answer['detail'] = '';

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
        } catch (ZCRMException $e) {
            Log::error($e);

            if (!empty($e->getExceptionDetails()))
                $answer['detail'] = $e->getExceptionDetails();
            else
                $answer['detail'] = $e->getMessage();
        }

        return ($answer);
    }

    public function updateZohoStripe(UpdateContractZohoRequest $request)
    {

        $dataUpdate = [
            'Email' => $request->email,
            'Monto_de_Anticipo' => $request->installment_amount,
            'Monto_de_Saldo' => $request->amount - $request->installment_amount,
            'Cantidad' => $request->installments,
            //Nro de cuotas
            'Valor_Cuota' => $request->installment_amount,
            //Costo de cada cuota
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

        if ($updateContract['result'] == 'error')
            return response()->json($updateContract, 500);
        else
            return response()->json($updateContract);
    }

    public function obtainData(Request $request)
    {

        $data = $request->all();

        $key = $data['key'];
        $id = $data['id'];

        $answer = [];
        $answer['detail'] = 'wrong key';
        $answer['status'] = 'error';

        if ($key == '9j9fj0Do204==3fja134') {
            $sale = $this->fetchRecordWithValue('Sales_Orders', 'id', $id, true);

            if ($sale != 'error') {
                $answer = [];
                $answer['products'] = [];

                $products = $sale->getLineItems();

                foreach ($products as $p) {
                    $newP = [];
                    $newP['name'] = $p->getProduct()->getLookupLabel();
                    $newP['quantity'] = $p->getQuantity();
                    $newP['id'] = $p->getId();
                    $newP['price'] = $p->getNetTotal();

                    $answer['products'][] = $newP;

                }
                $answer['sale'] = $sale->getData();

                $contactId = $sale->getFieldValue('Contact_Name')->getEntityId();

                $contact = $this->fetchRecordWithValue('Contacts', 'id', $contactId, true);

                $answer['contact'] = $contact->getData();

                $answer['status'] = 'ok';

            } else {
                $answer['detail'] = 'sale not found';
                $answer['status'] = 'error';
            }
        }

        if ($answer['status'] == 'error')
            return response()->json($answer, 500);
        else
            return response()->json($answer);
    }

    public function createLead(Request $request)
    {
        $data = $request->all();

        $data['profession'] = Profession::where('id', $data['profession'])->first()->name;
        $data['speciality'] = Speciality::where('id', $data['speciality'])->first()->name;
        $data['method_contact'] = MethodContact::where('id', $data['method_contact'])->first()->name;
        $data['user_email'] = $request->user()->email;

        $leadData = $this->processLeadData($data);

        $leadIsDuplicate = $this->updateFetchDuplicateLeads($leadData['Email']);

        if ($leadIsDuplicate)
            $leadData['Lead_Duplicado'] = true;

        $newLead = $this->createNewRecord('Leads', $leadData);

        if ($newLead['result'] == 'error')
            return response()->json($newLead, 500);
        else
            return response()->json($newLead);
    }

    public function createContact(Request $request)
    {
        $data = $request->all();

        //lo primero que haremos es intentar crear el contacto
        $contactData = array(
            'First_Name' => $data['name'],
            'Last_Name' => $data['username'],
            'Email' => $data['email'],
            'DNI' => $data['dni'],
            'Home_Phone' => $data['telephone'],
            'Pais' => $data['country'],
        );

        $newContact = $this->createNewRecord('Contacts', $contactData);

        if ($newContact['result'] == 'error')
            return response()->json($newContact, 500);
        else
            return response()->json($newContact);
    }

    private function createAddress($data)
    {
        $answer = [];
        $answer['id'] = '';
        $answer['result'] = '';
        //Guardo contacto en variable
        $contactData = $data['contact'];

        //armamos data de la dire y la creamos
        $addressData = array(
            'Calle' => $contactData['street'],
            'C_digo_Postal' => $contactData['postal_code'],
            'Name' => 'direccion',
            'Contacto' => $data['contact_id'],
            'Provincia' => $contactData['province_state'],
            'Pais' => $contactData['country'],
            'Localidad1' => $contactData['locality'],
            'Tipo_Dom' => "Particular"
        );

        //primero vamos a ver si existe una dirección con el mismo ID de contacto
        //para no repetir
        $existAddress = $this->fetchRecordWithValue('Domicilios', 'Contacto', $data['contact_id']);

        //esto significa que no existe
        if ($existAddress == 'error') {
            $newAddress = $this->createNewRecord('Domicilios', $addressData);
        } else //en cambio, si existe, actualizo
        {
            $newAddress = $this->updateRecord('Domicilios', $addressData, $existAddress->getEntityId());
        }

        return ($newAddress);
    }

    public function createAddressRequest(Request $request)
    {
        $data = $request->all();

        $address = $this->createAddress($data);

        if ($address['result'] == 'error')
            return response()->json($address, 500);
        else
            return response()->json($address);
    }

    public function createSale(Request $request)
    {
        $progress = PurchaseProgress::find($request->idPurchaseProgress);
        $products = $progress->contract->products->toArray();

        //armo el product details en base a las cosas que compró el usuario...
        $productDetails = $this->buildProductDetails($products);

        if ($productDetails != 'error') {
            $saleData = array(
                'Subject' => 'etc',
                //*
                'Status' => 'Contrato Pendiente',
                //*
                'Contact_Name' => $progress->contact->entity_id_crm,
                //'Cantidad' => $data['installments'],
                //'Fecha_de_Vto' => date('Y-m-d'),//*
                //'L_nea_nica_6' => $data['name'],
                //'L_nea_nica_3' => $data['identification'],
                //'Billing_Street' => $data['address'],
                //'Tipo_De_Pago' => $data['payment_type'],
                '[products]' => $productDetails,
                //* producto->id
                'Pais' => $progress->country,
                //'Anticipo' => strval($data['payment_in_advance']),
                //'Cuotas_restantes_sin_anticipo' => $data['left_installments'],
                //'Medio_de_Pago' => $data['left_payment_type'],
                //'Cuotas_totales' => 1,//*
                'Currency' => $progress->contract->currency,
                //'Modalidad_de_pago_del_Anticipo' => $data['left_payment_type'],
                //'Tipo_IVA' => 'Consumidor Final - ICF',
            );

            $newSale = $this->createRecordSale($saleData);

            if ($newSale['result'] == 'error') {
                return response()->json($newSale, 500);
            } else {
                $progress->contract->update(['entity_id_crm' => $newSale['id']]);
                return response()->json($newSale);
            }

        } else {
            $answer['id'] = '';
            $answer['result'] = 'error';

            return response()->json(['detail' => 'SKU incorrect'], 500);
        }


    }

    public function createRecordSale($data)
    {
        $answer = array();
        $answer['id'] = '';
        $answer['result'] = 'error';
        $answer['detail'] = '';

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

            if ($responseIns->getHttpStatusCode() == 201) {
                $answer['result'] = 'ok';
                $aux = $responseIns->getDetails();
                $answer['id'] = $aux['id'];
            }
        } catch (ZCRMException $e) {

            if (!empty($e->getExceptionDetails()))
                $answer['detail'] = $e->getExceptionDetails();
            else
                $answer['detail'] = $e->getMessage();

            Log::error($e);

        }

        return ($answer);
    }


    public function createRecordQuote($data)
    {
        $answer = array();
        $answer['id'] = '';
        $answer['result'] = 'error';
        $answer['detail'] = '';

        try {
            $record = ZCRMRestClient::getInstance()->getRecordInstance("Quotes", null); // To get record instance
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

                $taxInstance1 = ZCRMTax::getInstance("5344455000002958477"); 
                $taxInstance1->setPercentage(10); 
                $taxInstance1->setValue(100); 
                $product->addLineTax($taxInstance1); 

                $record->addLineItem($product);
            }

            $responseIns = $record->create();

            if ($responseIns->getHttpStatusCode() == 201) {
                $answer['result'] = 'ok';
                $aux = $responseIns->getDetails();
                $answer['id'] = $aux['id'];
            }
        } catch (ZCRMException $e) {

            if (!empty($e->getExceptionDetails()))
                $answer['detail'] = $e->getExceptionDetails();
            else
                $answer['detail'] = $e->getMessage();

            Log::error($e);

        }

        return ($answer);
    }

    //arma el detalle de productos para el contrato
    private function buildProductDetails($products)
    {
        $answer = array();
        //arma y reemplaza sku por ID de producto en zoho
        foreach ($products as $p) {
            $p['product_code'] = trim($p['product_code']); //Remove whitespace from product_code
            $rec = $this->fetchRecordWithValue('Products', 'Product_Code', $p['product_code']);

            if ($rec != 'error') {
                $answer[] = array(
                    'Product Id' => $rec->getEntityId(),
                    //*
                    'Quantity' => (int) $p['quantity'],
                    'List Price' => (float) $p['price'],
                    //'List Price #USD' => (float)$p['price_usd'],
                    //'List Price #Local Currency' => (float)$p['price'],
                    'Discount' => (float) $p['discount']
                );
            } else {
                $answer = "error";
                break;
            }
        }


        return ($answer);
    }

    public function convertLead(Request $request)
    {

        $genderOptions = [
            (object) ['id' => 1, 'name' => 'Masculino'],
            (object) ['id' => 2, 'name' => 'Femenino'],
            (object) ['id' => 3, 'name' => 'Prefiero no aclararlo']
        ];
        $progress = PurchaseProgress::where('id', $request->idPurchaseProgress)->first();
        $userOfProgress = User::find($progress->user_id);
        $leadInProgress = $progress->lead->toArray();

        $data = $request->all();
        $leadId = $data['lead_id'];

        $gender = collect($genderOptions)->firstWhere('id', $data['contact']['sex'])->name;

        $additionalData = [
            'DNI' => $data['contact']['dni'],
            'Sexo' => $gender,
            'Date_of_Birth' => $data['contact']['date_of_birth'],
            'Nro_Matr_cula' => $data['contact']['registration_number'],
            'rea_donde_trabaja' => $data['contact']['area_of_work'],
            'Inter_s_de_Formaci_n' => $data['contact']['training_interest'],
            'Plataforma' => 'Venta Presencial',
        ];

        $fetchContact = $this->fetchRecordWithValue("Contacts", 'DNI', $additionalData['DNI']);

        if ($fetchContact != 'error') {
            $entityId = $fetchContact->getEntityId();
            $leadConvertToContact = "El lead no fue convertido, se encontro un contacto con el mismo DNI y se utilizo el contacto ya existente";
            $additionalData['First_Name'] = $leadInProgress["name"];
            $additionalData['Last_Name'] = $leadInProgress["username"];
            $additionalData['Telefono_infobip'] = $leadInProgress["telephone"];
            $additionalData['Home_Phone'] = $leadInProgress["telephone"];
            $additionalData['Phone'] = $leadInProgress["telephone"];
            $additionalData['Email'] = $leadInProgress["email"];
            $additionalData['Fuente_de_Lead'] = array(0 => 'Venta Presencial'); //hay que definir donde buscamos el dato
            $additionalData['FUENTE'] = 'Venta Presencial'; //hay que definir donde buscamos el dato
            $additionalData['Plataforma'] = 'Venta Presencial';
            $additionalData['Lead_Status'] = 'Contacto urgente';
            $additionalData['Pais'] = $progress->country;
            $additionalData['pp'] = $leadInProgress["profession"];
            $additionalData['Especialidad'] = [$leadInProgress["speciality"]];
            $additionalData['Canal_de_Contactaci_n'] = [$leadInProgress["method_contact"]];
            $additionalData['EIRL'] = $userOfProgress['email'];
            $leadModule = ZCRMRestClient::getInstance()->getModuleInstance('Leads');
            $leadModule->deleteRecords([$leadId]);
        } else {
            $leadConvertToContact = $this->convertRecord($leadId, 'Leads');

            if (!empty($leadConvertToContact['id'])) {
                $entityId = $leadConvertToContact['id'];
            }
        }

        $updatedContact = $this->updateRecord("Contacts", $additionalData, $entityId, false);

        $addressParams = array_merge($data, ['contact_id' => $entityId]);
        $address = $this->createAddress($addressParams);

        if ($address['result'] == 'error' || $updatedContact['result'] == 'error') {
            return response()->json(['lead' => $leadConvertToContact, 'contact' => $updatedContact, 'address' => $address], 500);
        } else {
            return response()->json(['lead' => $leadConvertToContact, 'contact' => $updatedContact, 'address' => $address]);
        }
    }

    private function convertRecord($id, $type)
    {
        $answer['result'] = 'error';
        $answer['id'] = '';
        $answer['detail'] = '';

        try {
            $record = ZCRMRestClient::getInstance()->getRecordInstance($type, $id); // To get record instance
            $contact = ZCRMRecord::getInstance("Contacts", null); // to get the record of deal in form of ZCRMRecord insatnce
            $details = array("overwrite" => true);
            $responseIn = $record->convert($contact, $details); // to convert record

            $answer['result'] = 'ok';
            $answer['id'] = $responseIn["Contacts"];

        } catch (ZCRMException $e) {
            $handle = $this->handleError($e, $type, []);

            if ($handle != 'error') {
                $answer['result'] = 'duplicate';
                $answer['id'] = $handle;
            } else {
                $answer['result'] = 'error';

                if (!empty($e->getExceptionDetails()))
                    $answer['detail'] = $e->getExceptionDetails();
                else
                    $answer['detail'] = $e->getMessage();

                Log::error($e);
            }
        }

        return ($answer);
    }


    private function processLeadData($data)
    {
        //hay contactos?
        if ($this->fetchRecordWithValue('Contacts', 'Email', $data["email"]) == "error") {
            $leadData['Es_Contacto'] = false;
        } else {
            $leadData['Es_Contacto'] = true;
        }

        $leadData['First_Name'] = $data["name"];
        $leadData['Last_Name'] = $data["username"];
        $leadData['Phone'] = $data["telephone"];
        $leadData['Email'] = $data["email"];
        $leadData['Fuente_de_Lead'] = array(0 => 'Venta Presencial'); //hay que definir donde buscamos el dato
        $leadData['FUENTE'] = 'Venta Presencial'; //hay que definir donde buscamos el dato
        $leadData['Plataforma'] = 'Venta Presencial';
        $leadData['Lead_Status'] = 'Contacto urgente';
        $leadData['Pais'] = $data["country"];
        $leadData['pp'] = $data["profession"];
        $leadData['Especialidad'] = [$data["speciality"]];
        $leadData['Canal_de_Contactaci_n'] = [$data["method_contact"]];
        $leadData['EIRL'] = $data["user_email"];
        $leadData['*owner'] = $this->emi_owner;

        return $leadData;
    }

    private function updateFetchDuplicateLeads($mail)
    {
        //hay leads con ese mail?
        $searchBy = "((Email:equals:" . $mail . ")and(Lead_Status:equals:Contacto urgente))";
        $sameUserLeads = $this->fetchRecords('Leads', $searchBy); //<-- busca records para saber si el usuario ya intentó comprar anteriormente

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

    public function getProducts(Request $request, $iso)
    {
        $data = $request->all();
        try {
            $response = Http::asForm()->post("https://www.oceanomedicina.net/proxy/proxy2.php?url=https://www.oceanomedicina.com/api_landing.php", ['pais' => $iso]);

            // Verificar si la respuesta HTTP fue exitosa
            if ($response->successful()) {
                $data = json_decode($response->body());

                return response()->json($data);
            } else {
                // Manejar posibles errores o excepciones
                return response()->json([
                    'error' => 'Error al obtener los productos'
                ], $response->status());
            }
        } catch (\Exception $e) {
            // Manejar excepciones no controladas
            return response()->json([
                'error' => 'Error al obtener los productos: ' . $e->getMessage()
            ], 500);
        }
    }

    //traer usuario
    public function getUser($id)
    {
        $answer = 'error';

        $record = null;

        try {

            $apiResponse = ZCRMOrganization::getInstance()->getUser($id);
            $user = array($apiResponse->getData());

            $answer = $user[0];

        } catch (\Exception $e) {
            Log::error($e);
        }

        return ($answer);

    }

    public function getProductsWithoutIso(Request $request)
    {
        $data = $request->all();
        try {
            $response = Http::asForm()->post("https://www.oceanomedicina.net/proxy/proxy2.php?url=https://www.oceanomedicina.com/api_landing.php", ['pais' => 'ar']);

            // Verificar si la respuesta HTTP fue exitosa
            if ($response->successful()) {
                $data = json_decode($response->body());

                return response()->json($data);
            } else {
                // Manejar posibles errores o excepciones
                return response()->json([
                    'error' => 'Error al obtener los productos'
                ], $response->status());
            }
        } catch (\Exception $e) {
            // Manejar excepciones no controladas
            return response()->json([
                'error' => 'Error al obtener los productos: ' . $e->getMessage()
            ], 500);
        }
    }

}
