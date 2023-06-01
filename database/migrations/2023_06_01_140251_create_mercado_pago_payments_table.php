<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMercadoPagoPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mercado_pago_payments', function (Blueprint $table) {
            $table->id();
            $table->string('checkout_id')->nullable()->default(null);
            $table->string('checkout_url')->nullable()->default(null);
            $table->string('so');
            $table->string('sub_id');
            $table->integer('event_id');
            $table->string('status');
            $table->string('status_detail');
            $table->string('date_approved')->nullable()->default(null);
            $table->boolean('send_crm');

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
        Schema::dropIfExists('mercado_pago_payments');
    }
}