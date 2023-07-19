<?php

namespace Database\Seeders;

use App\Models\SourceLead;
use Illuminate\Database\Seeder;

class SourceLeadSeeder extends Seeder
{
    public $data;

    public function __construct()
    {
        $this->data = [
            ["name" => "Congresos"],
            ["name" => "Hospitales / Clínicas"],
            ["name" => "Visita personal"],
            ["name" => "Otros"]
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
            SourceLead::create($u);
        }
    }
}