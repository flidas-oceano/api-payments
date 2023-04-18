<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('addresses')->insert([
            'type_of_address'=>'Casa1',
            'country'=>'Argentina',
            'postal_code'=>'1177',
            'street'=>'Calle falsa 1',
            'locality'=>'Quilmes1',
        ]);
        DB::table('addresses')->insert([
            'type_of_address'=>'Casa2',
            'country'=>'Argentina',
            'postal_code'=>'2277',
            'street'=>'Calle falsa 2',
            'locality'=>'Quilmes2',
        ]);DB::table('addresses')->insert([
            'type_of_address'=>'Casa3',
            'country'=>'Argentina',
            'postal_code'=>'3377',
            'street'=>'Calle falsa 3',
            'locality'=>'Quilmes3',
        ]);DB::table('addresses')->insert([
            'type_of_address'=>'Casa4',
            'country'=>'Argentina',
            'postal_code'=>'4477',
            'street'=>'Calle falsa 4',
            'locality'=>'Quilmes4',
        ]);
    }
}

// Schema::create('addresses', function (Blueprint $table) {
//     $table->id();
//     $table->string('type_of_address');
//     $table->string('country');
//     $table->string('postal_code');
//     $table->string('street');
//     $table->string('locality');
