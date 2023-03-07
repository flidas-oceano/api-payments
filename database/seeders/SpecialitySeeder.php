<?php

namespace Database\Seeders;

use App\Models\Speciality;
use Illuminate\Database\Seeder;

class SpecialitySeeder extends Seeder
{

    public $data = [
        ['name' => 'Anestesiología'],
        ['name' => 'Diagnóstico por Imágenes'],
        ['name' => 'Cardiología'],
        ['name' => 'Cirugía'],
        ['name' => 'Cuidados críticos e intensivos'],
        ['name' => 'Dermatología'],
        ['name' => 'Emergentología'],
        ['name' => 'Endocrinología'],
        ['name' => 'Gastroenterología'],
        ['name' => 'Generalista - Clínica - Medicina interna'],
        ['name' => 'Geriatría y Gerontología'],
        ['name' => 'Ginecología'],
        ['name' => 'Hematología'],
        ['name' => 'Infectología'],
        ['name' => 'Internación domiciliaria y cuidados paliativos'],
        ['name' => 'Nefrología'],
        ['name' => 'Neonatología'],
        ['name' => 'Neurología'],
        ['name' => 'Nutrición y alimentación'],
        ['name' => 'Obstetricia'],
        ['name' => 'Obstetricia y Ginecología'],
        ['name' => 'Odontología'],
        ['name' => 'Oftalmología'],
        ['name' => 'Oncología'],
        ['name' => 'Ortopedia y Traumatología'],
        ['name' => 'Otorrinolaringología'],
        ['name' => 'Pediatría'],
        ['name' => 'Psiquiatría'],
        ['name' => 'Psicología'],
        ['name' => 'Radiología'],
        ['name' => 'Obstetricia y/o Ginecología'],
        ['name' => 'Bombero'],
        ['name' => 'Guardavidas'],
        ['name' => 'Bioimágenes'],
        ['name' => 'Fonoaudiología'],
        ['name' => 'Kinesiología'],
        ['name' => 'Policía'],
        ['name' => 'Rescatista'],
        ['name' => 'Internación'],
        ['name' => 'Psiquiatría / salud mental'],
        ['name' => 'Anestesia'],
        ['name' => 'Prácticas cardiológicas'],
        ['name' => 'Instrumentación quirúrgica'],
        ['name' => 'Podología'],
        ['name' => 'Hemoterapia e inmunohematología'],
        ['name' => 'Cosmetología'],
        ['name' => 'Medicina'],
        ['name' => 'Enfermería'],
        ['name' => 'Tecnicatura universitaria'],
        ['name' => 'Licenciatura en salud'],
        ['name' => 'Otra carrera'],
        ['name' => 'Medicina Familiar'],
        ['name' => 'Medicina Deportiva'],
        ['name' => 'Urología'],
        ['name' => 'Fisiatría'],
        ['name' => 'Alergología'],
        ['name' => 'Medicina del Trabajo'],
        ['name' => 'Fisioterapia General'],
        ['name' => 'Fisioterapia Respiratoría'],
        ['name' => 'Cirugía Vascular'],
        ['name' => 'Medicina Interna'],
        ['name' => 'Medicina General'],
        ['name' => 'Geriatría'],
        ['name' => 'Otra Especialidad'],
        ['name' => 'Sin Contacto'],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach($this->data as $d){
            Speciality::create($d);
        }
    }
}
