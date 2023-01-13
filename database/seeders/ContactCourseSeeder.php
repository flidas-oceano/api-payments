<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class ContactCourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('contact_courses')->insert([
            'woocommerce_course_id_crm' => '',
            'contacts_id_fk' => 1,
        ]);
        DB::table('contact_courses')->insert([
            'woocommerce_course_id_crm' => '',
            'contacts_id_fk' => 2,
        ]);
        DB::table('contact_courses')->insert([
            'woocommerce_course_id_crm' => '',
            'contacts_id_fk' => 3,
        ]);
        DB::table('contact_courses')->insert([
            'woocommerce_course_id_crm' => '',
            'contacts_id_fk' => 4,
        ]);
    }
}
