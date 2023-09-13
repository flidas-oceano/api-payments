<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumsToPlacetopaySubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('placetopay_subscriptions', function (Blueprint $table) {
            $table->string("date_to_pay")->nullable()->default(null);
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
            $table->dropColumn('date_to_pay');
        });
    }
}
