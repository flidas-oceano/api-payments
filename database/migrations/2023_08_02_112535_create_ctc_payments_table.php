<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCtcPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ctc_payments', function (Blueprint $table) {
            $table->id();
            
            $table->string("folio_pago");
            $table->string("folio_suscripcion");
            $table->string("so_contract");
            $table->integer("quotes");

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
        Schema::dropIfExists('ctc_payments');
    }
}
