<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeadsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('entity_id_crm');
            $table->string('name');
            $table->string('username');
            $table->string('telephone');
            $table->string('lead_status');
            $table->string('source_lead');
            $table->string('lead_source');

            $table->foreignId('method_contact_id_fk')
                    ->references('id')
                    ->on('method_contacts');
            $table->foreignId('contact_id_fk')
                    ->references('id')
                    ->on('contacts');
            $table->foreignId('addresses_id_fk')
                    ->references('id')
                    ->on('addresses');
            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leads');
    }
}
