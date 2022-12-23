<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('contacts')->insert([
            'entity_id_crm' => '',
            'username' => 'Londra',
            'date_of_birth' => '11/02/1999',
            'registration_number' => '35468150',
            'training_interest' => 'Enfermeria',
        ]);
        DB::table('contacts')->insert([
            'entity_id_crm' => '',
            'username' => 'Londra2',
            'date_of_birth' => '11/02/1999',
            'registration_number' => '35468150',
            'training_interest' => 'Enfermeria',
        ]); 
        DB::table('contacts')->insert([
            'entity_id_crm' => '',
            'username' => 'Londra3',
            'date_of_birth' => '11/02/1999',
            'registration_number' => '35468150',
            'training_interest' => 'Enfermeria',
        ]); 
        DB::table('contacts')->insert([
            'entity_id_crm' => '',
            'username' => 'Londra4',
            'date_of_birth' => '11/02/1999',
            'registration_number' => '35468150',
            'training_interest' => 'Enfermeria',
        ]);
    }
}
