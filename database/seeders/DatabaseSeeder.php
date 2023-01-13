<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\MethodContact;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        $this->call(AddressSeeder::class);
        $this->call(ContactSeeder::class);
        $this->call(MethodContact::class);
        $this->call(LeadSeeder::class);
        $this->call(CountrySeeder::class);
        $this->call(PurchasingProcessSeeder::class);
        $this->call(ContactCourseSeeder::class);
    }
}
