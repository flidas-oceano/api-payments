<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class PurchasingProcessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('purchasing_processes')->insert([
            'title' => 'Venta presencial',
            'country_id_fk' => 1,
            'lead_id_fk' => 1,
        ]);
        DB::table('purchasing_processes')->insert([
            'title' => 'Venta presencial',
            'country_id_fk' => 1,
            'lead_id_fk' => 2,
        ]);
        DB::table('purchasing_processes')->insert([
            'title' => 'Venta presencial',
            'country_id_fk' => 1,
            'lead_id_fk' => 3,
        ]);
        DB::table('purchasing_processes')->insert([
            'title' => 'Venta presencial',
            'country_id_fk' => 1,
            'lead_id_fk' => 4,
        ]);
    }
}
