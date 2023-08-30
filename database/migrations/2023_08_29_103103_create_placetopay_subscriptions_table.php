<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlacetopaySubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('placetopay_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->string("status")->nullable()->default(null);
            $table->string("reason")->nullable()->default(null);
            $table->string("message")->nullable()->default(null);
            $table->string("date")->nullable()->default(null);
            $table->string("requestId")->nullable()->default(null);
            $table->string("contact_id")->nullable()->default(null);//buyer
            $table->string("authorization")->nullable()->default(null);
            $table->string("total")->nullable()->default(null);
            $table->string("currency")->nullable()->default(null);
            $table->integer("nro_quote")->nullable()->default(null);
            $table->string("reference")->nullable()->default(null);
            $table->string("type")->nullable()->default(null);
            $table->string("token_collect_para_el_pago")->nullable()->default(null);//para realizar el pago de la suscripcion
            $table->string("expiration_date")->nullable()->default(null);

            $table->foreignId('transactionId')->nullable()->default(null)
            ->references('id')
            ->on('placetopay_transactions');

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
        Schema::dropIfExists('placetopay_subscriptions');
    }
}
