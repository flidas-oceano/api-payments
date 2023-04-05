<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public $data;

    public function __construct(){
        $this->data = [
            ["name" => "Juan Quiroga Cancino", "email" => "taz_jq@hotmail.com" , "password" => Hash::make("password")],
            ["name" => "Luis Piña", "email" => "luis.pina.carvajal@gmail.com" , "password" => Hash::make("password")],
            ["name" => "Admin", "email" => "tomasgomez@oceano.com.ar" , "password" => Hash::make("admin")],

        ];
    }
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->data as $u) {
            User::create($u);
        }
    }
}
