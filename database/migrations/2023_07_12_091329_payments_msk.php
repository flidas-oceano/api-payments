<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PaymentsMsk extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments_msk', function (Blueprint $table) {
            $table->id();
            $table->string('sub_id', 255)->nullable()->default(null);
            $table->string('charge_id', 255)->nullable()->default(null);
            $table->string('contact_id', 255)->nullable()->default(null);
            $table->string('contract_id', 255)->nullable()->default(null);
            $table->integer('number_installment')->nullable()->default(1);
            $table->string('payment_origin', 255)->nullable()->default(null);
            $table->string('external_number', 255)->nullable()->default(null);
            $table->string('number_so', 255)->nullable()->default(null);
            $table->string('number_so_om', 255)->nullable()->default(null);
            $table->dateTime('payment_date')->nullable()->default(null);
            $table->float('fee')->nullable()->default(0.0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments_msk');
    }
}
