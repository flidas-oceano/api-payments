<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToPlacetopayTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('placetopay_transactions', function (Blueprint $table) {
            $table->integer("quotes")->nullable()->default(null);
            $table->integer("first_installment")->nullable()->default(null);
            $table->integer("remaining_installments")->nullable()->default(null);
            $table->integer("installments_paid")->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('placetopay_transactions', function (Blueprint $table) {
            $table->dropColumn("quotes");
            $table->dropColumn("first_installment");
            $table->dropColumn("remaining_installments");
            $table->dropColumn("installments_paid");
        });
    }
}
