<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public $data;

    public function __construct()
    {

        $this->data = [
            ["name" => "Juan Quiroga Cancino", "email" => "juanquiroga@msklatam.tech", "password" => Hash::make("JuanChile2023")],
            ["name" => "Luis Piña", "email" => "luispina@msklatam.tech", "password" => Hash::make("LuisChile2023")],
            ["name" => "Josue Lara", "email" => "josuelara@msklatam.tech", "password" => Hash::make("JosueChile2023")],
            ["name" => "Cristian Contreras", "email" => "cristiancontreras@msklatam.tech", "password" => Hash::make("CristianChile2023")],
            ["name" => "Marcelo Salazar", "email" => "marcelosalazar@msklatam.tech", "password" => Hash::make("MarceloChile2023")],
            ["name" => "Enrique Rogers", "email" => "enriquerogers@msklatam.tech", "password" => Hash::make("EnriqueChile2023")],
            ["name" => "Alex Leiva", "email" => "alexleiva@msklatam.tech", "password" => Hash::make("AlexChile2023")],
            ["name" => "Mauricio Farias", "email" => "mauriciofarias@msklatam.tech", "password" => Hash::make("MauricioChile2023")],
            ["name" => "Sergio Platz", "email" => "sergioplatz@msklatam.tech", "password" => Hash::make("SergioChile2023")],
            ["name" => "Katerine Alvarez", "email" => "katerinealvarez@msklatam.tech", "password" => Hash::make("KaterineChile2023")],
            ["name" => "Jose Miguel Mella", "email" => "josemiguelmella@msklatam.tech", "password" => Hash::make("JoseChile2023")],
            ["name" => "Admin", "email" => "tomasgomez@oceano.com.ar", "password" => Hash::make("password")],
            ["name" => "Admin", "email" => "fbrizuela@oceano.com.ar", "password" => Hash::make("password")],
            ["name" => "Rober", "email" => "admin@oceano.com.ar", "password" => Hash::make("password")],

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