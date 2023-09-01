<?php

namespace Database\Seeders;

use App\Models\CronosElements;
use Illuminate\Database\Seeder;

class CronosElementsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /* CronosElements::create([
            'when_date' => '2023-06-09',
            'so_number' => '2000339000593300131',
            'data' => '{"contrato":{"propietario de contrato":"90100 - Matu00edas  Figueroa","nro de cuotas":"12","total general":"23880","dni":"593300131","cuit":"LUSX7812113Y2","nombre y apellido":"Xochiquetzal Luengas Sanchez","razon social":"Xochiquetzal Luengas Su00e1nchez","caracteristica contacto":"Experiencia OM","requiere factura":"Si requiere","email":"xochy1@hotmail.com","tipo iva":"ICF","tipo iva puro":"Consumidor Final - ICF","fecha contrato efectivo":"2023-06-09","acuerdo":"OM","numero de so":"2000339000593300131","anticipo 1er cuota":"1990","cod medio de pago de cuotas restantes":"?","medio de pago de cuotas restantes":"Mercado Pago","domicilio de facturacion":"Av Juarez 1123","fecha vto 1er cuota":"2023-06-09","cod modalidad de pago del anticipo":41,"modalidad de pago del anticipo":"Mercado pago (Vs)","moneda":"MXN","id contrato":"2000339000593300128","estado de contrato":"Contrato Efectivo AUDITADO","pais":"Mu00e9xico","cuotas totales":"12","es ecommerce":false,"es suscripcion":"1","monto_dolares":1343.84,"tasa":17.77,"organizacion":"medicina","stripe_subscription_id":"","mp_subscription_id":"5c2f5eaae9ff4d7e944f0521c2103dc7","banco emisor":"Otro Banco","tipo de cuenta":"","telefono facturacion":"2291620077","num de cuenta":"","regimen fiscal":"612 Personas fu00edsicas con actividades empresariales y profesionales","notas":"","fecha cobro diferido":"","distribuidor":"261","rut":"","codigo_combo":"ignore","tipo de contrato":"DI","tipo de vendedor":"VD","base":"Mu00e9xico"},"contacto":{"dni":"","nombre de contacto":"Luengas Su00e1nchez, Xochiquetzal","correo electronico":"xochy1@hotmail.com","telefono particular":"522291620077","otro telefono":"","pais":"Mu00e9xico","profesion o estudio":"Mu00e9dico","especialidad":"Dermatologu00eda","lead origen":"MKT On line","estado civil":"","genero":"Femenino","fecha de nacimiento":"1978-12-11","lugar de trabajo":"Consulta Privada - Veracruz","area de trabajo":"Consulta Privada - Veracruz","relacion laboral":"","cbu":""},"domicilio":{"tipo dom":"Particular","calle y nro":"CALLE 1 NO. 2B","piso/dpto":"","barrio":"COL. CENTRO EL TEJAR","localidad":"MPO. MEDELLu00cdN DE BRAVO, VERACRUZ","codigo postal":"94273","region":"","ciudades":"","pais":"Mu00e9xico","provincia":""},"cursos":[{"categoria de curso":"CURSO","codigo de curso":"9005701","#":0,"cantidad":1,"total":23880,"descuento":0,"precio de lista":23880,"nombre proveedor":"OD"},{"categoria de curso":"CURSO","codigo de curso":"9004738","#":1,"cantidad":0,"total":0,"descuento":0,"precio de lista":0,"nombre proveedor":"OD"}],"tipo_operacion":"add"}',
            'type' => 'add',
            'status' => 'pending',
            'processed' => 1,
            'log' => '{"message":"This contract is already registered!","code":1003,"data":["ERROR CREATE ORDER: This contract is already registered!"]}',
            'esanet' => 0,
            'error_lime_to_esanet' => 0,
            'sent_to_foc' => 0,
            'msk' => 1
        ]); */


        CronosElements::create([
            'when_date' => '2023-08-18',
            'so_number' => '2000339000617515006',
            'type' => 'add',
            'status' => 'pending',
            'data' => '[{"Owner":{"name":"Integraciones Administrador","id":"2000339000010971001","email":"sistemas@oceano.com.ar"},"CBU1":null,"$field_states":null,"Organizacion":null,"Error_cronos":null,"Cuotas_Cobradas":null,"$state":"save","$process_flow":false,"Currency":"USD","Resultado_del_cobro":null,"Certificaciones":null,"id":"2000339000617515005","Razon_Social":"-","Importe_Cobrado":null,"mp_subscription_id":"123123123123","Fecha_Creaci_n":"2023-07-21T14:08:00-03:00","course_names":null,"Cuotas_totales":12,"Status":"Contrato Cancelado - por error","$approval":{"delegate":false,"approve":false,"reject":false,"resubmit":false},"Tipo_de_acuerdo":null,"Adjustment":0,"Created_Time":"2023-07-21T14:08:21-03:00","Tipo_de_Cuenta":"Cuenta de ahorros","Suscripcion_con_Parcialidad":false,"Estado_Pago_De_Cuotas":null,"Caracter_stica_contacto":"Experiencia OM","Cantidad":12,"stripe_subscription_id":null,"RFC_Solo_MX":"-","Tel_fono_Facturacion":"561155011250","Created_By":{"name":"Integraciones Administrador","id":"2000339000010971001","email":"sistemas@oceano.com.ar"},"PRE_LANZAMIENTO":false,"Discount":0,"Caso":null,"course_template":null,"$review_process":{"approve":false,"reject":false,"resubmit":false},"Cuotas_restantes_sin_anticipo":11,"prueba":"test","Banco_emisor":"1 - SCI PRODUBANCO","Cargado_ESANET":false,"respuesta_mp":null,"Monto_de_cuotas_restantes":52.18,"Documento_CSF":null,"email_emblue":"pruebaCL+2000339000617515005@oceano.com.ar","Account_Name":null,"Valor_Cuota":52.18,"L_nea_nica_3":"123123123123","Anticipo":52.18,"Bonificar":null,"Combos":null,"Terms_and_Conditions":"test\ntest2\ntest3","Sub_Total":626.19,"Gesti_n_de_Cobranza":null,"$orchestration":false,"Contact_Name":{"name":"Tomas Gonzalo Gomez","id":"2000339000547596377"},"L_nea_nica_6":"Tomas Gonzalo Gomez","SO_Number":"2000339000617515006","Saldo":574.01,"N_mero_de_Cuenta":"123123123","Locked__s":false,"$line_tax":[],"Tag":[],"Fuente_de_Contrato":null,"Email":"pruebaCL@oceano.com.ar","$currency_symbol":"$","Es_Ecommerce":false,"Tipo_de_Documento":null,"Tax":0,"folio_suscripcion":"123456","Fecha_Contrato_Efectivo":"2023-08-18","Importe_NC":null,"Financiera_PY":null,"Medio_de_Pago":"Placetopay","Ticket_Rapipago":null,"$converted":false,"Exchange_Rate":1,"Fecha_de_Vto":"2023-08-18","$locked_for_me":false,"Facturado_MSK":false,"Periodos_Pagos":null,"$approved":true,"Grand_Total":626.19,"Bonificado_Suscri":false,"Billing_Street":"Miro 2008","Propietario_externo":null,"$editable":true,"Product_Details":[{"product":{"Product_Code":"9005722","Currency":"USD","name":"Patolog\u00eda Traum\u00e1tica en Urgencias Pedi\u00e1tricas","id":"2000339000544887471"},"quantity":1,"Discount":0,"total_after_discount":626.19,"net_total":626.19,"book":null,"Tax":0,"list_price":626.19,"unit_price":500,"quantity_in_stock":0,"total":626.19,"id":"2000339000617515008","product_description":null,"line_tax":[]},{"product":{"Product_Code":"9005711","Currency":"USD","name":"Ecograf\u00eda\u00a0Cl\u00ednica en Urgencias de Pediatr\u00eda","id":"2000339000521918152"},"quantity":1,"Discount":0,"total_after_discount":0,"net_total":0,"book":null,"Tax":0,"list_price":0,"unit_price":400,"quantity_in_stock":0,"total":0,"id":"2000339000617515010","product_description":null,"line_tax":[]}],"Tipo_de_Contrato":null,"Cupones":null,"Membresia":null,"Pendiente_de_Aprob":false,"Observaciones_del_asesor":null,"Error_ESANET":null,"Cliente_MX":"617515006","Incobrabilidad":10,"Calculo_de_cuota_CO":null,"Sin_Anticipo_de_primera_cuota_EC":false,"Es_Ebook":false,"Red_Habitab_1515":null,"Es_Suscri":true,"Venta_a_instituci_n":false,"Fecha_cobro_diferido":null,"Modified_By":{"name":"Alejandra Abdala","id":"2000339000006016096","email":"sistemas1@oceano.com.ar"},"$review":null,"C_digo_de_Combo":null,"CBU":null,"folio_pago":"123456","Descuento_Plataforma_Pagos":null,"Pais":"Ecuador","Costo_financiero":15,"Modified_Time":"2023-09-01T16:18:33-03:00","Modalidad_de_pago_del_Anticipo":"Debito en cuenta","Numero_de_operaci_n":"123","Requiere_factura":"No requiere","Fecha_Baja":"2023-07-31","N_mero_de_movimiento":null,"Subject":"DE ESPECIALIZACION","Acuerdo":"OCEANO MEDICINA - OM","Tipo_IVA":"Consumidor Final - ICF","CUIT_CUIL":null,"$in_merge":false,"Regimen_fiscal":"616 Sin obligaciones fiscales","Orden_de_compra_N":null,"Tipo_De_Pago":null,"$approval_state":"approved","Motivos":"Informaci\u00f3n incorrecta"}]',
            'log' => NULL,
            'processed' => NULL,
            'esanet' => '0',
            'error_lime_to_esanet' => NULL,
            'sent_to_foc' => NULL,
            'msk' => 1,
        ]);
    }
}