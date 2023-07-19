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
            $table->string('entity_id_crm')->nullable()->default(null);
            $table->string('lead_status')->nullable()->default(null);
            $table->string('name')->nullable()->default(null);
            $table->string('username')->nullable()->default(null);
            $table->string('email')->nullable()->default(null);
            $table->string('telephone')->nullable()->default(null);

            $table->foreignId('profession')->nullable()->default(null)
                ->references('id')
                ->on('professions');

            $table->foreignId('speciality')->nullable()->default(null)
                ->references('id')
                ->on('specialities');

            $table->foreignId('method_contact')->nullable()->default(null)
                ->references('id')
                ->on('method_contacts');

            $table->foreignId('contact_id')->nullable()->default(null)
                ->references('id')
                ->on('contacts');

            $table->foreignId('source_lead')->nullable()->default(null)
                ->references('id')
                ->on('sources_lead');

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