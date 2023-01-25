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
            $table->string('lead_status')->nullable();
            $table->string('source_lead')->nullable();
            $table->string('lead_source')->nullable();
            $table->string('name')->nullable();
            $table->string('username')->nullable();
            $table->string('email')->nullable();
            $table->string('telephone')->nullable();

            $table->foreignId('profession')
                ->nullable()
                ->references('id')
                ->on('professions')->onDelete('cascade')->onUpdate('cascade');

            $table->foreignId('speciality')
                ->nullable()
                ->references('id')
                ->on('specialities')->onDelete('cascade')->onUpdate('cascade');

            $table->foreignId('method_contact')
                ->nullable()
                ->references('id')
                ->on('method_contacts')->onDelete('cascade')->onUpdate('cascade');

            $table->foreignId('contact_id')
                ->nullable()
                ->references('id')
                ->on('contacts')->onDelete('cascade')->onUpdate('cascade');

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
        Schema::dropIfExists('leads');
    }
}
