<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchasingProcessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchasing_processes', function (Blueprint $table) {
            $table->id();

            $table->string('title');

            $table->foreignId('country_id_fk')
                    ->references('id')
                    ->on('countries');
            $table->foreignId('lead_id_fk')
                    ->references('id')
                    ->on('leads');

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
        Schema::dropIfExists('purchasing_processes');
    }
}
