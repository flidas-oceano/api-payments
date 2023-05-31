<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebhooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id('id');
            $table->dateTime('moment', 0);
            $table->string('type',50);
            $table->string('country',20);
            $table->string('event_id',200);
            $table->string('so',40);
            $table->string('status',60);
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('webhooks');
    }
}
