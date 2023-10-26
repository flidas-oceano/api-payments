<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeyToPlacetopayTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('placetopay_transactions', function (Blueprint $table) {
            $table->foreignId('flow_spp_id')
                ->nullable()
                ->default(null)
                ->references('id')
                ->on('flows_spp');
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
            $table->dropForeign(['flow_spp_id']);
            $table->dropColumn('flow_spp_id');
        });
    }
}
