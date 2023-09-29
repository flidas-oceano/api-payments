<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZohoControllerTest extends TestCase
{

    public function testUpdateZohoPTP()
    {
        // Supongamos que necesitas enviar ciertos datos en la solicitud
        $data = [
            'requestId' => null,
        ];
        // Simula una solicitud POST a la ruta /api/updateZohoPTP
        $response = $this->json('POST', '/api/updateZohoPTP', $data );

        $response->assertStatus(500)
            ->assertExactJson(['message' => 'Este es un mensaje diferente.']);
        // Verifica el código de estado de la respuesta (200 OK en este caso)
        $response->assertStatus(200);
        // Define la estructura esperada del JSON
        $response->assertExactJson ([
            'Email' => 'brizuelafacundoignacio@gmail.com',
            'Anticipo' => null,
            'Saldo' => 208.73,
            'Cantidad' => 3,
            'Monto_de_cuotas_restantes' => 208.73,
            'Cuotas_restantes_sin_anticipo' => null,
            'DNI' => '1234567890123',
            'Fecha_de_Vto' => '2023-09-29',
            'Status' => 'Contrato Efectivo',
            'Modalidad_de_pago_del_Anticipo' => 'Placetopay',
            'Medio_de_Pago' => 'Placetopay',
            'Es_Suscri' => 'VERDADERO',
            'Suscripcion_con_Parcialidad' => 'FALSO',
            'L_nea_nica_6' => 'Tomas Gonzalo Gomez',
            'Billing_Street' => null,
            'L_nea_nica_3' => '1234567890123',
            'Tel_fono_Facturacion' => '+541123081637',
            'Discount' => 0,
        ]);
    }
}
