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
            $table->string('entity_id_crm')->nullable();
            $table->string('name')->nullable();
            $table->string('username')->nullable();
            $table->string('telephone')->nullable();
            $table->string('lead_status')->nullable();
            $table->string('source_lead')->nullable();
            $table->string('lead_source')->nullable();
            $table->string('area_of_work')->nullable();
            $table->string('profession')->nullable();
            $table->string('specialty')->nullable();
            $table->string('dni')->nullable();
            $table->string('sex')->nullable();

            $table->foreignId('method_contact_id_fk')
                    ->nullable()        
                    ->references('id')
                    ->on('method_contacts');
            $table->foreignId('contact_id_fk')
                    ->nullable()        
                    ->references('id')
                    ->on('contacts');
            $table->foreignId('addresses_id_fk')
                    ->nullable()        
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
