<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumsToPlacetopaySubscriptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('placetopay_subscriptions', function (Blueprint $table) {
            $table->integer("failed_payment_attempts")->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('placetopay_subscriptions', function (Blueprint $table) {
            $table->integer("failed_payment_attempts")->nullable()->default(null);
        });
    }
}
