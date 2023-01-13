<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();

            $table->string('entity_id_crm')->nullable();
            $table->string('dni')->nullable();
            $table->string('sex')->nullable();
            $table->string('date_of_birth')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('area_of_work')->nullable();
            $table->string('training_interest')->nullable();

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
        Schema::dropIfExists('contacts');
    }
}
