<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCronosElementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::create('cronos_elements', function (Blueprint $table) {
        //     $table->id();
        //     $table->date('when_date');
        //     $table->string('so_number', 90);
        //     $table->string('type', 60);
        //     $table->string('status', 100);
        //     $table->text('data')->nullable();
        //     $table->text('log')->nullable();
        //     $table->tinyInteger('processed')->nullable();
        //     $table->tinyInteger('esanet')->nullable();
        //     $table->tinyInteger('error_lime_to_esanet')->nullable();
        //     $table->tinyInteger('send_to_foc')->nullable();
        //     $table->tinyInteger('msk')->nullable();
        //     $table->timestamps();
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cronos_elements');
    }
}