<?php

namespace Database\Seeders;

use App\Models\Profession;
use Illuminate\Database\Seeder;

class ProfessionSeeder extends Seeder
{
    public $data = [
        [
            'name' => "Médico"
        ],
        [
            'name' => "Lic. de la Salud"
        ],
        [
            'name' => "Técnico Universitario"
        ],
        [
            'name' => "Enfermeros / Auxiliares"
        ],
        [
            'name' => "Enfermero"
        ],
        [
            'name' => "Auxiliar de enfermería"
        ],
        [
            'name' => "Residentes / Estudiantes"
        ],
        [
            'name' => "Residente"
        ],
        [
            'name' => "Estudiante"
        ],
        [
            'name' => "Sin Contacto"
        ],
        [
            'name' => "Fuerza Pública"
        ],
        [
            'name' => "Otra Profesión"
        ],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach($this->data as $d){
            Profession::create($d);
        }
    }
}
