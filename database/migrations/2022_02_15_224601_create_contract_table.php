<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();

            $table->string('installments')->nullable()->default(null);
            $table->string('entity_id_crm')->nullable()->default(null);
            $table->string('Fecha_de_Vto')->nullable()->default(null);
            $table->string('lead_source')->nullable()->default(null);
            $table->string('name')->nullable()->default(null);
            $table->string('address')->nullable()->default(null);
            $table->string('payment_type')->nullable()->default(null);
            $table->string('country')->nullable()->default(null);
            $table->string('is_sub')->nullable()->default(null);
            $table->string('payment_in_advance')->nullable()->default(null);
            $table->string('left_installments')->nullable()->default(null);
            $table->string('left_payment_type')->nullable()->default(null);
            $table->string('currency')->nullable()->default(null);

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
        Schema::dropIfExists('contracts');
    }
}
