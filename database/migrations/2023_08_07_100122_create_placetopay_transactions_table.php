<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlacetopayTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('placetopay_transactions', function (Blueprint $table) {
            $table->id();

            $table->string("status")->nullable()->default(null);
            $table->string("reason")->nullable()->default(null);
            $table->string("message")->nullable()->default(null);
            $table->string("date")->nullable()->default(null);
            $table->string("requestId")->nullable()->default(null);
            $table->string("processUrl")->nullable()->default(null);
            $table->string("contact_id")->nullable()->default(null);//buyer
            $table->string("authorization")->nullable()->default(null);
            // $table->string("total")->nullable()->default(null);//TODO: cambiar de string a valor de con coma,
            $table->decimal('total', 10, 2)->nullable()->default(null);
            $table->string("currency")->nullable()->default(null);
            $table->string("reference")->nullable()->default(null);
            $table->string("type")->nullable()->default(null);
            $table->string("token_collect_para_el_pago")->nullable()->default(null);//para realizar el pago de la suscripcion
            $table->string("expiration_date")->nullable()->default(null);
            $table->integer("quotes")->nullable()->default(null);
            // $table->integer("first_installment")->nullable()->default(null);//TODO: cambiar de string a valor de con coma,
            $table->decimal('first_installment', 10, 2)->nullable()->default(null);
            // $table->integer("remaining_installments")->nullable()->default(null);//TODO: cambiar de string a valor de con coma,
            $table->decimal('remaining_installments', 10, 2)->nullable()->default(null);
            $table->integer("installments_paid")->nullable()->default(null);

            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     *
     * php artisan migrate:rollback --path=database/migrations/2023_08_07_100122_create_placetopay_transactions_table.php
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('placetopay_transactions');
    }
}
