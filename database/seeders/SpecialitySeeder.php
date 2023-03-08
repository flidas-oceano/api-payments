<?php

namespace Database\Seeders;

use App\Models\Speciality;
use Illuminate\Database\Seeder;

class SpecialitySeeder extends Seeder
{

    public $data = [
        ["name" => "Anestesiología"],
        ["name" => "Diagnóstico por Imágenes"],
        ["name" => "Cardiología"],
        ["name" => "Cirugía"],
        ["name" => "Cuidados críticos e intensivos"],
        ["name" => "Dermatología"],
        ["name" => "Emergentología"],
        ["name" => "Endocrinología"],
        ["name" => "Gastroenterología"],
        ["name" => "Generalista - Clínica - Medicina interna"],
        ["name" => "Geriatría y Gerontología"],
        ["name" => "Ginecología"],
        ["name" => "Hematología"],
        ["name" => "Infectología"],
        ["name" => "Internación domiciliaria y cuidados paliativos"],
        ["name" => "Nefrología"],
        ["name" => "Neonatología"],
        ["name" => "Neurología"],
        ["name" => "Nutrición y alimentación"],
        ["name" => "Obstetricia"],
        ["name" => "Obstetricia y Ginecología"],
        ["name" => "Odontología"],
        ["name" => "Oftalmología"],
        ["name" => "Oncología"],
        ["name" => "Ortopedia y Traumatología"],
        ["name" => "Otorrinolaringología"],
        ["name" => "Pediatría"],
        ["name" => "Psiquiatría"],
        ["name" => "Radiología"],
        ["name" => "Otra Especialidad"]
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->data as $d) {
            Speciality::create($d);
        }
    }
}
