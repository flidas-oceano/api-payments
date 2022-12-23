<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('leads')->insert([
            'entity_id_crm' => '',
            'name' => 'Paulo',
            'username' => 'Londra',
            'telephone' => '1223325665',
            'lead_status' => '1',
            'source_lead' => '2',
            'lead_source' => '3',
            'method_contacts_id_fk' => 1,
            'contact_id_fk' => '1',
            'addresses_id_fk' => '1',
        ]);
        DB::table('leads')->insert([
            'entity_id_crm' => '',
            'name' => 'Paulo2',
            'username' => 'Londra2',
            'telephone' => '1223325665',
            'lead_status' => '1',
            'source_lead' => '2',
            'lead_source' => '3',
            'method_contacts_id_fk' => 1,
            'contact_id_fk' => '2',
            'addresses_id_fk' => '2',
        ]);
        DB::table('leads')->insert([
            'entity_id_crm' => '',
            'name' => 'Paulo3',
            'username' => 'Londra3',
            'telephone' => '1223325665',
            'lead_status' => '1',
            'source_lead' => '2',
            'lead_source' => '3',
            'method_contacts_id_fk' => 1,
            'contact_id_fk' => '3',
            'addresses_id_fk' => '3',
        ]);
        DB::table('leads')->insert([
            'entity_id_crm' => '',
            'name' => 'Paulo4',
            'username' => 'Londra4',
            'telephone' => '1223325665',
            'lead_status' => '1',
            'source_lead' => '2',
            'lead_source' => '3',
            'method_contacts_id_fk' => 1,
            'contact_id_fk' => '4',
            'addresses_id_fk' => '4',
        ]);
    }
}
