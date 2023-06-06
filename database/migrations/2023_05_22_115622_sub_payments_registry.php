<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SubPaymentsRegistry extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sub_payments_registry', function (Blueprint $table) {
            $table->id();
            $table->string('number_so_om', 255)->nullable(true);
            $table->double('amount')->nullable(true);
            $table->string('payment_id', 255)->nullable(true);
            $table->dateTime('pay_date')->nullable(true);
            $table->string('pay_state', 20)->nullable(true);
            $table->string('gateway', 255)->nullable(true);
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
        Schema::dropIfExists('sub_payments_registry');
    }
}
