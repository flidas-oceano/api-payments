<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePendingPaymentsRebillTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pending_payments_rebill', function (Blueprint $table) {
            $table->id();
            $table->string('payment_id');
            $table->string('status');
            $table->string('type');
            $table->string('subscription_id')->nullable()->default(null);
            $table->string('contract_id');
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
        Schema::dropIfExists('pending_payments_rebill');
    }
}