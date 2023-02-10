<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->string('quantity')->nullable();
            $table->string('price')->nullable();
            $table->string('discount')->nullable();

            $table->foreignId('contract_id')
                ->nullable()
                ->references('id')
                ->on('contracts')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('products');
    }
}
