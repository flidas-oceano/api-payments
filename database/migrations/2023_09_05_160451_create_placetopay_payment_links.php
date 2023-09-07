<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlacetopayPaymentLinks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('placetopay_payment_links', function (Blueprint $table) {
            $table->id();

            $table->foreignId("transactionId")
                ->references('id')
                ->on("placetopay_transactions");
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
        Schema::dropIfExists('placetopay_payment_links');
    }
}

// php artisan migrate:rollback --step=1 --path=/database/migrations/2023_09_05_160451_create_placetopay_payment_links.php
// php artisan migrate --path=/database/migrations/2023_09_05_160451_create_placetopay_payment_links.php
