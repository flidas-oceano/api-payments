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
            $table->string('type_of_address')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('street')->nullable();
            $table->string('locality')->nullable();
            $table->string('province_state')->nullable();

            $table->foreignId('lead_id')
                    ->nullable()        
                    ->references('id')
                    ->on('leads')->onDelete('cascade')->onUpdate('cascade');
                    
            $table->timestamps();
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
