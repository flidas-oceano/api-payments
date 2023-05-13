<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentLinksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payment_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId("rebill_customer_id")->references('id')->on("rebill_customers")->onUpdate('cascade')->onDelete('cascade');
            $table->string("gateway");
            $table->string("type");
            $table->integer("quotes");
            $table->string("contract_entity_id");
            $table->string("contract_so");
            $table->string("status");
            $table->string("country");
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
        Schema::dropIfExists('payment_links');
    }
}