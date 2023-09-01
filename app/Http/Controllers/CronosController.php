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

class CronosController extends Controller
{


    public $zoho_api, $spain_url, $NewZoho;

    //--------------------------------
    //--------------------------------
    //--------------------------------
    //--------------------------------
    //--------------------------------
    //--------------------------------
    //--------------------------------
    //Funciones POST GET
    //--------------------------------
    //--------------------------------
    //--------------------------------
    //--------------------------------
    //--------------------------------
    //--------------------------------
    //--------------------------------

    public function __construct()
    {

        $this->spain_url = 'api';
        $this->zoho_api = 'd8eff47754322c7a4ba86d13f25c5772';
        $this->NewZoho = App::make('App\Http\Controllers\ZohoController');

        /*
        if ($this->_env == 'prod') //prode
        {
        $this->spain_url = 'api';
        $this->zoho_api = 'd8eff47754322c7a4ba86d13f25c5772';
        }
        else if ($this->_env == 'test') //test
        {
        $this->spain_url = 'api-dev';
        $this->zoho_api = 'e25b3b8fd44657334be72a513f129502';
        }
        */
    }

    public function deletecontract()
    {
        if (isset($_POST['response'])) {
            $response = json_decode($_POST['response']);
            $data = $response->data;

            $aux = $data[0];

            Log::info('[delete] Llegó contrato: ' . $aux->SO_Number);

            $hasSavedNewElement = $this->uploadElement($data, 'delete', 'pending');

            return response()->json(['hasSavedNewElement' => $hasSavedNewElement]);
        }
    }

    public function addcontract()
    {
        if (isset($_POST['response'])) {
            $response = json_decode($_POST['response']);
            $data = $response->data;

            $aux = $data[0];

            Log::info('[add] Llegó contrato: ' . $aux->SO_Number);

            $hasSavedNewElement = $this->uploadElement($data, 'add', 'pending');

            return response()->json(['hasSavedNewElement' => $hasSavedNewElement]);
        }
    }

    private function uploadElement($data, $type, $status)
    {
        $answer = null;
        $so_numb = $data[0]->SO_Number;

        //codifico data
        $data = json_encode($data);

        $element = CronosElements::create([
            'when_date' => date("Y-m-d"),
            'data' => $data,
            'so_number' => $so_numb,
            'status' => $status,
            'type' => $type,
            'esanet' => 0
        ]);

        Log::info("Guarde el element", ['element' => $element]);

        try {
            $element->save();
            $answer = [
                'saved' => true,
                'element' => $element
            ];
        } catch (\Throwable $e) {
            Log::error($e);

            $answer = [
                'saved' => false,
                'error' => $e
            ];
        }

        return ($answer);
    }

    public function test()
    {
        echo 'ay hola';

        $this->NewZoho->reinit();

        // $exists = $this->NewZoho->fetchRecordWithValue('Quotes', 'otro_so', 2000339000574080235);
        //$exists = $this->NewZoho->fetchRecordWithValue('Quotes', 'Quote_Number', "5344455000003383107");
        // $exists = $this->NewZoho->fetchRecordWithValue('Contacts', 'id', "5344455000003383061");

        $contactId = '';
        $exists = $this->NewZoho->fetchRecordWithValue('Quotes', 'otro_so', "123415");

        if ($exists != 'error')
            $contactId = $exists->getFieldValue('Contact_Name')->getEntityId();
        else {
            $exists = $this->NewZoho->fetchRecordWithValue('Sales_Orders', 'otro_so', "12345");

            if ($exists != 'error')
                $contactId = $exists->getFieldValue('Contact_Name')->getEntityId();
        }
    }

    private function post_spain_delete($so)
    {
        $answer = array();
        $answer['answer'] = 'nope';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'http://' . $this->spain_url . '.oceano.com/api/v1/shop/order/cancellation/' . $so);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        //curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");

        $headers = array();
        $headers[] = 'api-key: oceano_argentina';
        $headers[] = 'api-token: $2y$10$0tF42wa79/7hVPvPNWVKXeNyjE6XHPp21T387reNCl2Lj/OUSq/tG';
        $headers[] = "Content-Type: application/json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        //verificamos que haya salido bien

        $encoded = json_decode($result);

        $answer['log'] = $result;

        if (isset($encoded->code)) {
            if ($encoded->code == 200)
                $answer['answer'] = 'ok';
        }

        return ($answer);
    }

    private function post_spain($data)
    {
        $answer = array();
        $answer['answer'] = 'nope';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'http://' . $this->spain_url . '.oceano.com/api/v1/shop/order');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_POST, 1);

        $headers = array();
        $headers[] = 'api-key: oceano_argentina';
        $headers[] = 'api-token: $2y$10$0tF42wa79/7hVPvPNWVKXeNyjE6XHPp21T387reNCl2Lj/OUSq/tG';
        $headers[] = "Content-Type: application/json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        //verificamos que haya salido bien

        $encoded = json_decode($result);
        Log::info(print_r($encoded, true));

        $answer['log'] = $result;

        if (isset($encoded->code)) {
            if ($encoded->code == 200)
                $answer['answer'] = 'ok';
            else if ($encoded->code == 1001)
                $answer['answer'] = 'country'; //no es argentina/uruguay
            else if ($encoded->code == 1003)
                $answer['answer'] = 'duplicate'; //ya lo tienen anotado...
            else if ($encoded->code == 1006)
                $answer['answer'] = 'nope'; //mal la estructura
        }


        return ($answer);
    }

    private function removeduplicates($elements)
    {
        $answer = [];

        foreach ($elements as $e) {


            foreach ($answer as $k => $a) {
                //existe
                if ($a->so_number == $e->so_number) {
                    //lo marco para omitir
                    $answer[$k]->status = 'omit';
                }
            }


            $answer[] = $e;
        }

        return ($answer);
    }


    ///limit=10&status=done_sucess&date_begin=2021-03-11
    //get a españa pero trae varios
    private function get_spain_filter($filter)
    {
        $answer = array();
        $answer['answer'] = 'error';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'http://' . $this->spain_url . '.oceano.com/api/v1/shop/order?' . $filter);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        //curl_setopt($ch, CURLOPT_POST, 1);

        $headers = array();
        $headers[] = 'api-key: oceano_argentina';
        $headers[] = 'api-token: $2y$10$0tF42wa79/7hVPvPNWVKXeNyjE6XHPp21T387reNCl2Lj/OUSq/tG';
        $headers[] = "Content-Type: application/json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        $answer = json_decode($result);

        return ($answer);
    }


    //hace get a españa, trae un contrato
    private function get_spain($id)
    {
        $answer = array();
        $answer['answer'] = 'error';

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'http://' . $this->spain_url . '.oceano.com/api/v1/shop/order/contract/' . $id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        //curl_setopt($ch, CURLOPT_POST, 1);

        $headers = array();
        $headers[] = 'api-key: oceano_argentina';
        $headers[] = 'api-token: $2y$10$0tF42wa79/7hVPvPNWVKXeNyjE6XHPp21T387reNCl2Lj/OUSq/tG';
        $headers[] = "Content-Type: application/json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
        }
        curl_close($ch);

        //verificamos que haya salido bien

        $encoded = json_decode($result);

        if (isset($encoded->code)) {
            $answer = $encoded;
        }


        return ($answer);
    }


    //--------------------------------
    //URL para CRON
    //--------------------------------


    private function processDeletions()
    {
        $elements = CronosElements::where('status', '=', 'pending')->where('type', '=', 'delete')->get();

        foreach ($elements as $e) {
            $spain = $this->post_spain_delete($e->so_number);

            Log::info('process deletion: ' . $e->so_number);

            if ($spain['answer'] == 'ok') {
                $e->status = "success";
            }

            $e->log = $spain['log'];
            $e->save();
        }
    }

    public function cronapi()
    {

        $packs = [];

        $elements = CronosElements::where('status', 'pending')->where('type', 'add')->get();

        $count = 0;

        $elements = $this->removeduplicates($elements);

        foreach ($elements as $k => $e) {

            Log::info('process element: ' . $e->so_number);

            $dataReady = ''; //para pasarle a LIME
            $pack = ''; //datos procesador, sin encodear

            $count++;

            $ignore = false;

            if ($e->status == 'omit')
                $ignore = true;

            if (!$ignore) {
                //primero vemos si se procesó alguna vez esto
                //de esa manera evitamos llamar a zoho al pedo en el futuro en caso de que españa no lo admita
                if (!$e->processed) {

                    $crude = json_decode($e->data);

                    //==========arranca empaquetado de datos

                    $pack = $this->packData($crude, $e->type);
                    //$pack['prueba'] = 1;

                    //====== termina empaquetado de datos

                    //dado que terminó de empaquetar (procesar)
                    //vamos a anotar esta info en la tabl y marcar como procesado
                    //por si sale mal, en el futuro no tiene que volver a empaquetar


                    $encodeToJson = json_encode($pack);

                    if ($encodeToJson === false) {
                        //apa, salió mal! no hagas nada!!!
                    } else {
                        $e->data = $encodeToJson;
                        $e->processed = true;
                    }


                    $dataReady = $encodeToJson;
                    $pack = $encodeToJson;
                } else {
                    //ya se procesó pero sigue pendiente, bueno, intentamos de nuevo!
                    //si falla es por problemas de españa...

                    $dataReady = $e->data;
                    $pack = $e->data;
                }
            }

            //caso especial
            $special = false;
            $packs[$e->id] = $pack;

            if (!$ignore) {
                $spainStatus = '';


                $datos = json_decode($dataReady, true);

                foreach ($datos['cursos'] as $c) {
                    if ($c['codigo de curso'] == '9005800' || $c['codigo de curso'] == '9005801' || $c['codigo de curso'] == '9005802' || $c['codigo de curso'] == '9005803' || $c['codigo de curso'] == '9005804')
                        $special = true;
                }
            }

            //primero lo mando a españa
            //lo mando a españa primero porque si lo mando y salió ok, es españa quien luego me dice
            //este contrato ya lo tengo, entonces en base a eso yo tengo el contrato en su estado final...
            //y ese es el cual uso para luego crear en MSK

            //envia a spain!

            // $special = true;
            if (!$ignore) {
                if (!$special) {
                    Log::info('envio a españa');
                    $what = $this->post_spain($dataReady);
                    // Log::info('españa responde ' . $what['log']);
                    $e->log = $what['log'];
                    //salió bien, cambia el estado
                    if ($what['answer'] == 'ok')
                        $e->msk = 1;
                    else {
                        if ($what['answer'] == 'duplicate') {
                            $e->msk = 1;
                        } else
                            if ($what['answer'] == 'country') {
                            }
                    }
                } else {
                    $e->msk = 1;
                }
            }

            //----
        }

        $this->NewZoho->reinit();

        foreach ($elements as $e) {

            Log::info('proceso para zoho crm ' . $e->so_number);

            $pack = json_decode($packs[$e->id], true);

            if ($e->msk == 1 && $e->status != 'omit') {

                //mandar a MSK
                //primero reviso que no esté en MSK
                $exists = $this->NewZoho->fetchRecordWithValue('Quotes', 'otro_so', $pack['contrato']['numero de so']);
                $exists2 = $this->NewZoho->fetchRecordWithValue('Sales_Orders', 'otro_so', $pack['contrato']['numero de so']);

                if ($exists == 'error' && $exists2 == 'error') {
                    //no existe, lo voy a crear
                    $result = $this->createMSK($pack);

                    if ($result) {
                        $e->status = "success";
                    }
                } else /* ya existe, update contact */{
                    $result = $this->updateMSK($pack);

                    if ($result)
                        $e->status = "success";
                }
            }


            try {
                $e->save();
            } catch (\Throwable $t) {

                Log::error(print_r($t, true));
            }
        }

        $this->processDeletions();

        return response()->json('proceso cronapi completado');
    }


    //--------------------------------
    //--------------------------------
    //--------------------------------
    //--------------------------------
    //--------------------------------
    //--------------------------------
    //--------------------------------
    //--------------------------------
    //Utiles
    //--------------------------------
    //--------------------------------
    //--------------------------------
    //--------------------------------
    //--------------------------------
    //--------------------------------
    //--------------------------------
    //--------------------------------
    //--------------------------------

    //empaqueta toda la data, le pasas en crudo lo que te da zoho... y el tipo de operación (es un add o edit?)

    private function packData($crude, $operation)
    {

        $answer = array();

        $orderData = $crude[0]; //tomamos todo junto, así de una, ya que la data esta dispersada con los arrays

        //método auxiliar para obtener el nombre del propietario de contrato
        $propietary = $this->NewZoho->getUser($crude[0]->Owner->id);

        $propFullName = $propietary->getFieldValue('Identificaci_n_de_empleado') . ' - ' . $propietary->getFirstName() . ' ' . $propietary->getLastName();

        $propCountry = $propietary->getCountry();

        $orderData->nombre_propietario = utf8_encode($propFullName);

        //data auxiliar necesario para los cursos/productos
        $auxProdata = array();

        //extraer ids
        $contactId = $crude[0]->Contact_Name->id;
        $productIds = array();

        foreach ($crude[0]->Product_Details as $pd) {

            $idpr = $pd->product->id;
            $productIds[] = $idpr;

            $auxProdata[$idpr] = array();
            $auxProdata[$idpr]['cantidad'] = $pd->quantity;
            $auxProdata[$idpr]['total'] = $pd->net_total;
            $auxProdata[$idpr]['descuento'] = $pd->Discount;
            $auxProdata[$idpr]['precio de lista'] = $pd->list_price;
        }


        //obtener datos
        $contactData = $this->NewZoho->fetchRecordWithValue('Contacts', 'id', $contactId);
        //$addressData = $this->NewZoho->fetchRecordWithValue('Domicilios','Contacto',$contactId);
        $addressData = $this->NewZoho->fetchRecords('Domicilios', '(Contacto:equals:' . $contactId . ')');
        //$addressData = $this->NewZoho->fetchRecords('Domicilios','(Contacto:equals:2000339000383191832)');

        $productsData = array();

        foreach ($productIds as $k => $pi) {
            $auxProdata[$pi]['#'] = $k;

            $prod = $this->NewZoho->fetchRecordWithValue('Products', 'id', $pi);

            $vendor = $prod->getFieldValue('Vendor_Name');
            if ($vendor != null)
                $vendor = $vendor->getLookupLabel();
            else
                $vendor = '';

            $productsData['#' . $k] = array(
                'Product Category' => $this->pax3($prod, 'Product_Category'),
                'Product Code' => $this->pax3($prod, 'Product_Code'),
                'Vendor Name' => $vendor
            );

            $productsData['#' . $k]['auxiliar_data'] = $auxProdata[$pi];
        }

        // empaquetado y transformación de los datos

        $pack = array();
        $pack['contrato'] = $this->transform($orderData, 'contrato');
        $pack['contacto'] = $this->transform($contactData, 'contacto');
        $pack['domicilio'] = $this->transform($addressData[0], 'domicilio');



        if (isset($addressData[1]))
            $pack['domicilio_2'] = $this->transform($addressData[1], 'domicilio');

        //dato adicional que necesito poner en contacto, pero que viene de contrato
        $pack['contacto']['cbu'] = $this->pax($orderData, 'CBU1');

        //si es URUGUAY, vamos a agregar dos datos más al paquete...

        if (strtolower($pack['contrato']['pais']) == 'uruguay') {
            $pack['contrato']['mail facturacion'] = $this->pax($orderData, 'Email');
            $pack['contrato']['telefono facturacion'] = $this->pax($orderData, 'Tel_fono_Facturacion');
        }


        if ($pack['contrato']['pais'] != 'Argentina') {
            unset($pack['contrato']['membresia']);
        }

        //si es MEX...

        if ($pack['contrato']['pais'] == 'México') {
            if ($propCountry == 'México')
                $pack['contrato']['distribuidor'] = '262';
            else
                $pack['contrato']['distribuidor'] = '261';

            if (!isset($crude[0]->RFC_Solo_MX) || $crude[0]->RFC_Solo_MX == null)
                $pack['contrato']['cuit'] = "XAXX010101000";
            else
                $pack['contrato']['cuit'] = str_replace(" ", "", $crude[0]->RFC_Solo_MX);

            $pack['contrato']['dni'] = $crude[0]->Cliente_MX;
        }

        $pack['contrato']['rut'] = '';

        if ($pack['contrato']['pais'] == 'Chile') {
            $pack['contrato']['rut'] = $pack['contrato']['dni'];
        }


        //--------
        if ($pack['contrato']['pais'] == 'Ecuador') {
            if ($pack['contrato']['es ecommerce'])
                $pack['contrato']['codigo_combo'] = 'ignore';
            else
                $pack['contrato']['codigo_combo'] = $this->pax($orderData, 'C_digo_de_Combo');

            $pack['contrato']['banco emisor'] = $this->pax($orderData, 'Banco_emisor');
            $pack['contrato']['tipo de cuenta'] = $this->pax($orderData, 'Tipo_de_Cuenta');
            $pack['contrato']['nro cuenta'] = $this->pax($orderData, 'N_mero_de_Cuenta');
            $pack['contrato']['nro operacion'] = $this->pax($orderData, 'Numero_de_operaci_n');
            $pack['contrato']['resultado cobro'] = $this->pax($orderData, 'Resultado_del_cobro');
            $pack['contrato']['nro movimiento'] = $this->pax($orderData, 'N_mero_de_movimiento');

            $pack['contacto']['tipo documento'] = $this->pax($orderData, 'Tipo_de_Documento');
            $pack['contacto']['dni'] = $this->pax($orderData, 'L_nea_nica_3');

        } else {
            $pack['contrato']['codigo_combo'] = 'ignore';
        }


        $pack['cursos'] = array();

        foreach ($productsData as $prod) {
            $pack['cursos'][] = $this->transform($prod, 'curso');
        }

        $pack['tipo_operacion'] = $operation;

        $pack = $this->adjustments($pack);

        $answer = $pack;

        return ($answer);
    }


    //le pasas el paquete y mira los datos y hace algunos cambios
    //si es necesario
    //esto es ajustes son en base a datos que ya tenés en el pack, o sea no podés acceder al sales_order crudo

    private function adjustments($pack)
    {

        if ($pack['contrato']['es ecommerce']) {
            //$pack['contrato']['propietario de contrato'] = '9501 - (ventas digitales)';
            //$pack['contrato']['distribuidor'] = '250';
            $pack['contacto']['lead origen'] = 'Ecommerce';

            if ($pack['contrato']['pais'] == 'México') {
                $pack['contrato']['IdInstitucion'] = 11;
            }
        } else {
            if ($pack['contacto']['lead origen'] == 'Ecommerce')
                $pack['contacto']['lead origen'] = 'MKT On line';
        }

        if (
            $this->has(
                strtolower($pack['contrato']['modalidad de pago del anticipo']),
                'mercado'
            ) &&
            $this->has(
                strtolower($pack['contrato']['modalidad de pago del anticipo']),
                'pago'
            ) &&
            $pack['contrato']['es suscripcion'] == "1"
        ) {
            $pack['contrato']['medio de pago de cuotas restantes'] = 'Mercado Pago';
        }


        //si es méxico va una cosa, sino otra
        if ($pack['contrato']['pais'] == 'México') {
            $pack['contrato']['tipo de contrato'] = 'DI';
            $pack['contrato']['tipo de vendedor'] = 'VD';
        } else if ($pack['contrato']['pais'] == 'Ecuador') {
            if ($pack['contrato']['es ecommerce'])
                $pack['contrato']['tipo de contrato'] = 'VDI';
            else
                $pack['contrato']['tipo de contrato'] = 'VD';

            $pack['contrato']['tipo de vendedor'] = 'DIG';
        } else {
            $pack['contrato']['tipo de contrato'] = 'VDI';
            $pack['contrato']['tipo de vendedor'] = 'VD';
        }

        //agrega base, esto presupone que arg y mx da igual si es ecom o no
        //tambien presupone que el resto SÍ son ecom
        if ($pack['contrato']['pais'] == 'Argentina' || $pack['contrato']['pais'] == 'México') {
            $pack['contrato']['base'] = $pack['contrato']['pais'];
        } else {
            if ($pack['contrato']['es ecommerce'])
                $pack['contrato']['base'] = 'LATAM';
            else
                $pack['contrato']['base'] = $pack['contrato']['pais'];
        }

        if ($pack['contrato']['pais'] == 'Costa Rica') {
            if ($pack['contrato']['propietario de contrato'] == '270006 - Kelyn Lorena Isaza') {
                $pack['contrato']['propietario de contrato'] = '270007 - Kelyn Lorena Isaza';
            }
        }


        return ($pack);
    }

    //verificar integridad de datos, podemos obtener toda la informacion necesaria?

    private function verify($data)
    {

        $answer = true;

        if (!isset($data[0]->Owner->id))
            $answer = false;

        if (!isset($data[0]->Contact_Name->id) || $this->NewZoho->fetchRecordWithValue('Domicilios', 'Contacto', $data[0]->Contact_Name->id) == 'error')
            $answer = false;

        if (!isset($data[0]->Product_Details))
            $answer = false;

        //si da mal, es porque falta owner id, contact, productos, o domicilio

        return ($answer);
    }

    //le pasas un dato y un tipo de filtro, y aplica la acción
    private function filter($data, $type = null)
    {
        $answer = $data;

        //separa por guion y se queda con el segundo dato
        if ($type == 'guion') {
            $parts = explode('-', $data);

            if (isset($parts[1]))
                $answer = trim($parts[1]);
        } else if ($type == "guion_1") //separa por guion y se queda con el primer dato
        {
            $parts = explode('-', $data);

            if (isset($parts[1])) //para quedarse con el primer dato, me interesa saber que efectivamente lo partió en 2!! sino no me sirve...
                $answer = trim($parts[0]);
        } else if ($type == "onlynumbers") //deja sólo numeros (mentira, porque deja los puntos)
        {
            $answer = preg_replace('/[^0-9.]/', '', $data);
        } else if ($type == "code_payment") {
            $answer = $this->tableCodPayment($data);
        }


        return ($answer);
    }




    //transforma los datos (obtiene los necesarios)
    private function transform($data, $who)
    {
        $answer = array();

        if ($who == 'contrato') {

            $exchange_rate = floatval($this->pax($data, 'Exchange_Rate'));

            if ($exchange_rate == 0)
                $exchange_rate++;

            $answer['propietario de contrato'] = $this->pax($data, 'nombre_propietario');
            //$answer['nro de cuotas'] = $this->pax($data,'Cantidad');
            $answer['nro de cuotas'] = $this->pax($data, 'Cuotas_totales');
            $answer['total general'] = $this->pax($data, 'Grand_Total');
            $answer['tipo documento'] = $this->pax($data, 'Tipo_de_Documento');
            $answer['dni'] = str_replace(".", "", $this->filter($this->pax($data, 'L_nea_nica_3')));
            $answer['cuit'] = $this->pax($data, 'CUIT_CUIL');
            $answer['nombre y apellido'] = $this->pax($data, 'L_nea_nica_6');
            $answer['razon social'] = $this->pax($data, 'Razon_Social');
            $answer['caracteristica contacto'] = $this->pax($data, 'Caracter_stica_contacto');
            $answer['requiere factura'] = $this->pax($data, 'Requiere_factura');
            $answer['email'] = $this->pax($data, 'Email');
            $answer['tipo iva'] = $this->filter($this->pax($data, 'Tipo_IVA'), 'guion');
            $answer['tipo iva puro'] = $this->pax($data, 'Tipo_IVA');
            $answer['fecha contrato efectivo'] = $this->pax($data, 'Fecha_Contrato_Efectivo');
            $answer['acuerdo'] = $this->filter($this->pax($data, 'Acuerdo'), 'guion');
            $answer['numero de so'] = $this->pax($data, 'SO_Number');
            $answer['anticipo 1er cuota'] = $this->pax($data, 'Anticipo');
            $answer['cod medio de pago de cuotas restantes'] = $this->filter($this->pax($data, 'Medio_de_Pago'), "code_payment");
            $answer['medio de pago de cuotas restantes'] = $this->pax($data, 'Medio_de_Pago');
            $answer['cuotas restantes'] = $this->pax($data, 'Cuotas_restantes_sin_anticipo');
            $answer['ajuste de cobro'] = $this->pax($data, 'Discount');
            $answer['monto cuotas restantes'] = $this->pax($data, 'Monto_de_cuotas_restantes');
            $answer['domicilio de facturacion'] = $this->pax($data, 'Billing_Street');
            $answer['fecha vto 1er cuota'] = $this->pax($data, 'Fecha_de_Vto');
            $answer['cod modalidad de pago del anticipo'] = $this->filter($this->pax($data, 'Modalidad_de_pago_del_Anticipo'), "code_payment");
            $answer['modalidad de pago del anticipo'] = $this->pax($data, 'Modalidad_de_pago_del_Anticipo');
            $answer['moneda'] = $this->pax($data, 'Currency');
            $answer['id contrato'] = $this->pax($data, 'id');
            $answer['estado de contrato'] = $this->pax($data, 'Status');
            $answer['pais'] = $this->pax($data, 'Pais');
            $answer['cuotas totales'] = $this->pax($data, 'Cuotas_totales');
            $answer['es ecommerce'] = (bool) $this->pax($data, 'Es_Ecommerce');
            $answer['es suscripcion'] = $this->pax($data, 'Es_Suscri');
            $answer['es suscripcion con parcialidad'] = $this->pax($data, 'Suscripcion_con_Parcialidad');
            $answer['monto_dolares'] = round(floatval($this->pax($data, 'Grand_Total')) / $exchange_rate, 2);
            $answer['tasa'] = $exchange_rate;
            $answer['organizacion'] = $this->pax($data, 'Organizacion');
            $answer['stripe_subscription_id'] = $this->pax($data, 'stripe_subscription_id');
            $answer['mp_subscription_id'] = $this->pax($data, 'mp_subscription_id');
            $answer['banco emisor'] = $this->pax($data, 'Banco_emisor');
            $answer['membresia'] = (int) filter_var($this->pax($data, 'Membresia'), FILTER_SANITIZE_NUMBER_INT);
            $answer['tipo de cuenta'] = $this->pax($data, 'Tipo_de_Cuenta');
            $answer['telefono facturacion'] = $this->pax($data, 'Tel_fono_Facturacion');
            $answer['num de cuenta'] = $this->pax($data, 'N_mero_de_Cuenta');
            $answer['regimen fiscal'] = $this->pax($data, 'Regimen_fiscal');
            //$answer['notas'] = $this->fetchNotes($this->pax($data,'id'));
            $answer['notas'] = '';
            $answer['folio pago'] = $this->pax($data, 'folio_pago');
            $answer['folio suscription'] = $this->pax($data, 'folio_suscripcion');

            $bonificar = intval($this->pax($data, 'Bonificar'));

            if ($bonificar > 0)
                $answer['fecha cobro diferido'] = $this->pax($data, 'Fecha_cobro_diferido');
            else
                $answer['fecha cobro diferido'] = '';


            if ($this->pax($data, 'Es_Ecommerce') == true) {
                //$answer['ecom_combos'] = $this->pax($data,'Combos');
                //$answer['ecom_cupones'] = $this->pax($data,'Cupones');
                //$answer['ecom_certificaciones'] = $this->pax($data,'Certificaciones');
            }
        } else if ($who == 'contacto') {
            $answer['tipo documento'] = $this->pax($data, 'Tipo_de_Documento');
            $answer['dni'] = str_replace(".", "", $this->filter($this->pax3($data, 'DNI')));
            $answer['nombre de contacto'] = $this->pax3($data, 'Last_Name') . ', ' . $this->pax3($data, 'First_Name');
            $answer['correo electronico'] = $this->pax3($data, 'Email');
            $answer['telefono particular'] = $this->filter($this->pax3($data, 'Home_Phone'), 'onlynumbers');
            $answer['otro telefono'] = $this->filter($this->pax3($data, 'Other_Phone'), 'onlynumbers');
            $answer['pais'] = $this->pax3($data, 'Pais');
            $answer['profesion o estudio'] = $this->pax3($data, 'prof');
            $answer['especialidad'] = $this->pax3($data, 'Especialidad');
            $answer['lead origen'] = $this->pax3($data, 'Fuente_de_lead_manual_prueba');
            $answer['estado civil'] = $this->pax3($data, 'Estado_Civil');
            $answer['genero'] = $this->pax3($data, 'Sexo');
            $answer['fecha de nacimiento'] = $this->pax3($data, 'Date_of_Birth');
            $answer['lugar de trabajo'] = $this->pax3($data, 'Lugar_de_Trabajo');
            $answer['area de trabajo'] = $this->pax3($data, 'rea_donde_trabaja');
            $answer['relacion laboral'] = $this->pax3($data, 'Relaci_n_Laboral'); //si luego el pais es diferente de ecuador, esto se saca
        } else if ($who == 'domicilio') {
            $answer['tipo dom'] = $this->pax3($data, 'Tipo_Dom');
            $answer['calle y nro'] = $this->pax3($data, 'Calle');
            $answer['piso/dpto'] = $this->pax3($data, 'Piso_Dpto');
            $answer['barrio'] = $this->pax3($data, 'Barrio');
            $answer['localidad'] = $this->pax3($data, 'Localidad1');
            $answer['codigo postal'] = $this->pax3($data, 'C_digo_Postal');
            $answer['region'] = $this->pax3($data, 'Regi_n');
            $answer['ciudades'] = $this->pax3($data, 'Ciudades');
            $answer['pais'] = $this->pax3($data, 'Pais');
            $answer['provincia'] = $this->filter($this->pax3($data, 'Provincia'), 'guion_1');
        } else if ($who == 'curso') {
            $answer['categoria de curso'] = $this->pax2($data, 'Product Category');
            $answer['codigo de curso'] = $this->pax2($data, 'Product Code');
            $answer['#'] = $this->pax2($data['auxiliar_data'], '#');
            $answer['cantidad'] = $this->pax2($data['auxiliar_data'], 'cantidad');
            $answer['total'] = $this->pax2($data['auxiliar_data'], 'total');
            $answer['descuento'] = $this->pax2($data['auxiliar_data'], 'descuento');
            $answer['precio de lista'] = $this->pax2($data['auxiliar_data'], 'precio de lista');
            $answer['nombre proveedor'] = $this->pax2($data, 'Vendor Name');
        }

        return ($answer);
    }


    private function tableCodContact($value, $type)
    {
        $table = array();
        $table['prof'] = array();
        $table['espec'] = array();
        $table['rel'] = array();

        $table['rel']['EMPLEADO PRIVADO'] = 'EPRIV';
        $table['rel']['EMPLEADO PÚBLICO'] = 'EPUB';

        $table['prof']['Fuerza Pública'] = 10;
        $table['prof']['Enfermeros / Auxiliares'] = 16;
        $table['prof']['Psicólogos y terapeutas'] = 46;
        $table['prof']['Residentes / Estudiantes'] = 53;
        $table['prof']['Otra Profesión'] = 7;
        $table['prof']['Médico'] = 8;

        $table['espec']['Otra Especialidad'] = 0;
        $table['espec']['Medicina General'] = 1;
        $table['espec']['Medicina Familiar'] = 2;
        $table['espec']['Pediatría'] = 3;
        $table['espec']['Obstetricia y/o Ginecología'] = 4;
        $table['espec']['Medicina Interna'] = 5;
        $table['espec']['Infectología'] = 6;
        $table['espec']['Dermatología'] = 7;
        $table['espec']['Cirugía'] = 8;
        $table['espec']['Cardiología'] = 9;
        $table['espec']['Cirugía Vascular'] = 10;
        $table['espec']['Hematología'] = 11;
        $table['espec']['Psicología'] = 12;
        $table['espec']['Neurología'] = 13;
        $table['espec']['Medicina Deportiva'] = 14;
        $table['espec']['Urología'] = 15;
        $table['espec']['Ortopedia y Traumatología'] = 16;
        $table['espec']['Fisiatría'] = 17;
        $table['espec']['Geriatría'] = 18;
        $table['espec']['Alergología'] = 19;
        $table['espec']['Emergentología'] = 20;
        $table['espec']['Cuidados críticos e intensivos'] = 21;
        $table['espec']['Medicina del Trabajo'] = 22;
        $table['espec']['Nefrología'] = 23;
        $table['espec']['Oncología'] = 24;
        $table['espec']['Otorrinolaringología'] = 25;
        $table['espec']['Generalista - Clínica - Medicina interna'] = 26;
        $table['espec']['Geriatría y Gerontología'] = 27;

        if (isset($table[$type][$value]))
            return $table[$type][$value];
        else
            return '?';
    }


    private function tableCodPayment($value)
    {
        $table = array();

        $table['Efectivo presencial (Vs)'] = 1;
        $table['Depósito bancario (Vs)'] = 2;
        $table['Transferencia bancaria (Vs)'] = 3;
        $table['PSE pagos (CO)'] = 4;
        $table['Recaudo expres (CO)'] = 5;
        $table['PAYPAL (MX)'] = 6;
        $table['Visanet (PE)'] = 7;
        $table['Webpay (CL)'] = 8;
        $table['Financiera (PY)'] = 9;
        $table['Cheques (CL)'] = 10;
        $table['Océano - Tarjeta de debito (Vs)'] = 11;
        $table['Red habitab 1515'] = 12;
        $table['Tarjeta OCA'] = 13;
        $table['Débito Brou'] = 14;
        $table['LAPOS (AR)'] = 38;
        $table['Mercado pago - Débito  (Vs)'] = 39;
        $table['Todo pago (AR)'] = 40;
        $table['Mercado pago (Vs)'] = 41;
        $table['WEBPOSNET'] = 42;
        $table['Stripe'] = 45;
        $table['Océano - Tarjeta de crédito (Vs)'] = 60;
        $table['C.B.U.'] = 70;
        $table['Cobro por Banco'] = 75;

        if (isset($table[$value]))
            return $table[$value];
        else
            return '?';
    }

    private function tableCodPaymentMX($value)
    {
        $table = array();

        $table['Océano - Tarjeta de debito (Vs)'] = 7;
        $table['Océano - Tarjeta de crédito (Vs)'] = 1;
        $table['Efectivo presencial (Vs)'] = 1;
        $table['Transferencia bancaria (Vs)'] = 1;
        $table['PAYPAL (MX)'] = 1;
        $table['Mercado pago - Débito  (Vs)'] = 1;
        $table['Mercado pago (Vs)'] = 1;

        if (isset($table[$value]))
            return $table[$value];
        else
            return '?';
    }

    private function fetchNotes($id)
    {

        return ($this->NewZoho->getNotes($id));
    }


    //le pasas un cambio en crudo y te devuelve el id de zoho
    private function extract_zoho_id($ch)
    {
        $answer = '';

        $data = json_decode($ch['data'], true);


        $answer = $this->pax($data[0], 'id');

        return ($answer);
    }


    //si no existe el valor en el field del array, devuelve vacio
    private function pax($array, $field)
    {
        $answer = '';

        if (isset($array->$field)) {
            $answer = $this->sanitize($array->$field);

            if (preg_match('//u', $answer) == 0) //sigue andando mal algo, probamos otro encodeo
                $answer = $this->clean_str($array->$field);
        }

        return ($answer);
    }

    //si no existe el valor en el field del array, devuelve vacio
    private function pax2($array, $field)
    {
        $answer = '';

        if (isset($array[$field])) {
            $answer = $array[$field];
        }

        return ($answer);
    }

    //si no existe el valor en el zcrm, devuelve vacio
    private function pax3($array, $field)
    {
        $answer = '';

        $thing = $array->getFieldValue($field);

        if ($thing != null) {
            if (is_array($thing)) {
                $answer = implode(' | ', $thing);
            } else
                $answer = $thing;
        } else
            $answer = '';


        return ($answer);
    }

    //lo limpia...
    private function sanitize($str)
    {

        $str = utf8_decode($str);

        return ($str);
    }


    //arregla el valor
    private function clean_str($str)
    {
        $utf8_ansi2 = array(
            "\u00c0" => "À",
            "\u00c1" => "Á",
            "\u00c2" => "Â",
            "\u00c3" => "Ã",
            "\u00c4" => "Ä",
            "\u00c5" => "Å",
            "\u00c6" => "Æ",
            "\u00c7" => "Ç",
            "\u00c8" => "È",
            "\u00c9" => "É",
            "\u00ca" => "Ê",
            "\u00cb" => "Ë",
            "\u00cc" => "Ì",
            "\u00cd" => "Í",
            "\u00ce" => "Î",
            "\u00cf" => "Ï",
            "\u00d1" => "Ñ",
            "\u00d2" => "Ò",
            "\u00d3" => "Ó",
            "\u00d4" => "Ô",
            "\u00d5" => "Õ",
            "\u00d6" => "Ö",
            "\u00d8" => "Ø",
            "\u00d9" => "Ù",
            "\u00da" => "Ú",
            "\u00db" => "Û",
            "\u00dc" => "Ü",
            "\u00dd" => "Ý",
            "\u00df" => "ß",
            "\u00e0" => "à",
            "\u00e1" => "á",
            "\u00e2" => "â",
            "\u00e3" => "ã",
            "\u00e4" => "ä",
            "\u00e5" => "å",
            "\u00e6" => "æ",
            "\u00e7" => "ç",
            "\u00e8" => "è",
            "\u00e9" => "é",
            "\u00ea" => "ê",
            "\u00eb" => "ë",
            "\u00ec" => "ì",
            "\u00ed" => "í",
            "\u00ee" => "î",
            "\u00ef" => "ï",
            "\u00f0" => "ð",
            "\u00f1" => "ñ",
            "\u00f2" => "ò",
            "\u00f3" => "ó",
            "\u00f4" => "ô",
            "\u00f5" => "õ",
            "\u00f6" => "ö",
            "\u00f8" => "ø",
            "\u00f9" => "ù",
            "\u00fa" => "ú",
            "\u00fb" => "û",
            "\u00fc" => "ü",
            "\u00fd" => "ý",
            "\u00ff" => "ÿ"
        );

        return strtr($str, $utf8_ansi2);
    }

    //el string tiene word?
    private function has($string, $word)
    {
        if (strpos($string, $word) !== false)
            return true;
        else
            return false;
    }

    //formato iso paises

    private function format_iso($country)
    {
        $iso = array(
            'Argentina' => 'ARG',
            'Bolivia' => 'BOL',
            'Chile' => 'CHL',
            'Colombia' => 'COL',
            'Ecuador' => 'ECU',
            'México' => 'MEX',
            'Panamá' => 'PAN',
            'Paraguay' => 'PRY',
            'Perú' => 'PER',
            'Uruguay' => 'URY'
        );

        return ($iso[$country]);
    }

    private function updateMSK($element)
    {
        $answer = false;

        $contactId = '';

        $exists = $this->NewZoho->fetchRecordWithValue('Quotes', 'otro_so', $element['contrato']["numero de so"]);

        if ($exists != 'error')
            $contactId = $exists->getFieldValue('Contact_Name')->getEntityId();
        else {
            $exists = $this->NewZoho->fetchRecordWithValue('Sales_Orders', 'otro_so', $element['contrato']["numero de so"]);

            if ($exists != 'error')
                $contactId = $exists->getFieldValue('Contact_Name')->getEntityId();
        }

        $contactData = $this->buildContact($element);

        $updateContact = $this->NewZoho->updateRecord("Contacts", $contactData, $contactId, false);

        Log::info("update contact", $updateContact);

        if ($updateContact['result'] != 'error')
            $answer = true;

        return ($answer);
    }

    private function buildContact($element)
    {
        $explodedName = explode(",", $element['contacto']["nombre de contacto"]);

        $surname = '-';
        $name = '-';
        $rut = $element['contrato']["rut"];

        if (isset($explodedName[0]))
            $surname = $explodedName[0];

        if (isset($explodedName[1]))
            $name = $explodedName[1];

        $razonsocial = $element['contrato']["razon social"];

        if ($razonsocial == '')
            $razonsocial = $element['contrato']["nombre y apellido"];

        if (isset($rut) && strpos($rut, '-') == false) {
            $rut = substr_replace($element['contrato']["rut"], "-", -1, 0);
        }

        $contactData = array(
            "ID_Personal" => $element['contacto']['dni'],
            "Identificacion" => $element['contacto']['dni'],
            "Tipo_de_Documento" => $element['contacto']['tipo documento'],
            'First_Name' => $name,
            'Last_Name' => $surname,
            'Email' => $element['contacto']["correo electronico"],
            'Phone' => $element['contacto']["telefono particular"],
            'Mobile' => $element['contacto']["otro telefono"],
            'Pais' => $element['contacto']["pais"],
            'Profesi_n' => $element['contacto']["profesion o estudio"],
            'Especialidad' => $element['contacto']["especialidad"],
            'Estado_civil' => $element['contacto']["estado civil"],
            "Sexo" => $element['contacto']["genero"],
            'Date_of_Birth' => $element['contacto']["fecha de nacimiento"],
            'Lugar_de_trabajo' => $element['contacto']["lugar de trabajo"],
            'rea_donde_tabaja' => $element['contacto']["area de trabajo"],
            'Mailing_Street' => $element['domicilio']["calle y nro"],
            'Mailing_Zip' => $element['domicilio']["codigo postal"],
            'Estado' => $element['domicilio']["region"],
            'City' => $element['domicilio']["localidad"],
            'Mailing_State' => $element['domicilio']["provincia"],
            'Caracter_stica_contacto' => $element['contrato']["caracteristica contacto"],

            'Requiere_factura' => $element['contrato']["requiere factura"],
            'R_gimen_fiscal' => $element['contrato']["regimen fiscal"],

            'RUT' => $rut,

            "CUIT_CUIL_o_DNI" => $element['contrato']["cuit"],
            "RFC" => $element['contrato']["cuit"],
            'Raz_n_social' => $razonsocial,
            "correo_facturacion" => $element['contrato']["email"]
        );

        return ($contactData);
    }

    private function createMSK($element)
    {
        $answer = false;

        //para ir viendo si avanzarf o no, los status
        $contactStatus = false;
        $saleStatus = false;
        $prodStatus = false;

        $contactData = $this->buildContact($element);

        $newContact = $this->NewZoho->createNewRecord('Contacts', $contactData);

        Log::info('new contact' . print_r($newContact, true));

        //si pudo crear bien el contacto, status ok
        if ($newContact['result'] == 'ok' || $newContact['result'] == 'duplicate') {
            $contactStatus = true;
        }

        //avanza si está bien todo, sino no
        if ($contactStatus) {
            //armo el product details en base a las cosas que compró el usuario...
            $productDetails = $this->buildProductDetails($element['cursos']);

            if ($productDetails != 'error') {
                $prodStatus = true;
            }
        }

        //Log::info('prod details' . print_r($productDetails, true));

        //si pudo crear los product details
        if ($prodStatus) {

            $owner = '5344455000001853001';

            $mododepago = '';

            if ($element['contrato']['es suscripcion con parcialidad'] == 1) {
                $mododepago = 'Cobro recurrente con parcialidad';
            } else if ($element['contrato']['es suscripcion'] == 1) {
                $mododepago = 'Cobro Recurrente';
            } else if ($element['contrato']['cuotas totales'] == 1) {
                $mododepago = 'Cobro total en un pago';
            }

            $sub_id = $element['contrato']["stripe_subscription_id"];

            if ($element['contrato']["mp_subscription_id"] != '')
                $sub_id = $element['contrato']["mp_subscription_id"];

            //armamos dato de la venta (contrato) y a crear
            $saleData = array(
                'Subject' => 'Contrato Oceano Medicina',
                'Contact_Name' => $newContact['id'],
                'Grand_Total' => $element['contrato']["total general"],

                'otro_so' => $element['contrato']["numero de so"],
                'Currency' => $element['contrato']["moneda"],
                'Quote_Stage' => $element['contrato']["estado de contrato"],
                'Pais_de_facturaci_n' => $element['contrato']["pais"],

                'Banco_emisor' => $element['contrato']['banco emisor'],
                'Tipo_de_cuenta' => $element['contrato']['tipo de cuenta'],
                'N_mero_de_Cuenta' => $element['contrato']['nro cuenta'],
                'Resultado_del_cobro' => $element['contrato']['resultado cobro'],
                'N_mero_de_movimiento' => $element['contrato']['nro movimiento'],

                'folio_pago' => isset($element['contrato']['folio pago']) ? $element['contrato']['folio pago'] : null,
                'folio_suscripcion' => isset($element['contrato']['folio suscription']) ? $element['contrato']['folio suscription'] : null,

                'Modo_de_pago' => $mododepago,
                'Monto_de_parcialidad' => $element['contrato']['anticipo 1er cuota'],
                'Cantidad_de_pagos_recurrentes_restantes' => $element['contrato']['cuotas restantes'],
                'Monto_de_cada_pago_restantes' => $element['contrato']['monto cuotas restantes'],
                'M_todo_de_pago' => $element['contrato']["modalidad de pago del anticipo"],
                'subscription_id' => $sub_id,
                'mp_subscription_id' => $sub_id,
                'Discount' => $element['contrato']['ajuste de cobro'],

                "Seleccione_total_de_pagos_recurrentes" => $element['contrato']["cuotas totales"],
                '[products]' => $productDetails,
                'Owner' => $owner
            );

            $newSale = $this->NewZoho->createRecordQuote($saleData);

            Log::info('new sale' . print_r($newSale, true));

            //si pudo crear bien el contrato, status ok
            if ($newSale['result'] == 'ok') {
                $saleStatus = true;
            }
        }

        if ($contactStatus && $saleStatus && $prodStatus)
            $answer = true;

        return ($answer);
    }

    //arma el detalle de productos para el contrato
    private function buildProductDetails($products)
    {
        $answer = array();
        $nonexistent = false;



        //arma y reemplaza sku por ID de producto en zoho
        foreach ($products as $p) {



            $rec = $this->NewZoho->fetchRecordWithValue('Products', 'Product_Code', $p['codigo de curso']);

            //trajo ok
            if ($rec != 'error') {

                $total = $p['precio de lista'];
                $discount = $p['descuento'];
                $perc = 0;

                if ($total > 0) {
                    $perc = ($total - $discount) * 100 / $total;
                    $perc = 100 - $perc;
                }

                //tax
                // $tax = (intval($p['precio de lista']) - intval($p['descuento'])) * 0.16;

                $answer[] = array(
                    'Product Id' => $rec->getEntityId(),
                    'Quantity' => $p['cantidad'],
                    'List Price' => intval($p['precio de lista']),
                    'Tax' => 0,
                    //'List Price #USD' => (float)$p['price_usd'],
                    //'List Price #Local Currency' => (float)$p['price'],
                    'Discount' => $perc
                );
            } else //dió error, entonces voy a romper todo a propósito así da mal el contrato
            {
                $nonexistent = true;
            }
        }

        if ($nonexistent)
            $answer = 'error';

        return ($answer);
    }
}