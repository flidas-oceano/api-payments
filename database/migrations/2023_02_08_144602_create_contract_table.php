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

            $table->string('installments')->nullable();
            $table->string('Fecha_de_Vto')->nullable();
            $table->string('lead_source')->nullable();
            $table->string('name')->nullable();
            $table->string('address')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('country')->nullable();
            $table->string('is_sub')->nullable();
            $table->string('payment_in_advance')->nullable();
            $table->string('left_installments')->nullable();
            $table->string('left_payment_type')->nullable();
            $table->string('currency')->nullable();

            $table->foreignId('contact_id')
                ->nullable()
                ->references('id')
                ->on('contacts')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contract');
    }
}
