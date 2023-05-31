<?php

namespace Database\Seeders;

use App\Models\MethodContact;
use Illuminate\Database\Seeder;
class MethodContactSeeder extends Seeder
{

    public $data = [
        ['name' => 'Telefono' ],
        ['name' => 'Whatsapp' ],
        ['name' => 'SMS' ],
        ['name' => 'Email' ],
    ];
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach($this->data as $d){
            MethodContact::create($d);
        }
    }
}
