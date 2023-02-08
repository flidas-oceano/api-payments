<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchasingProcessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_progress', function (Blueprint $table) {
            $table->id();
            $table->integer('step_number',false, true);
            $table->string('country')->nullable();
            $table->string('title')->nullable();
            $table->foreignId('lead_id')
                    ->nullable()        
                    ->references('id')
                    ->on('leads')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('contract_id')
                    ->nullable()        
                    ->references('id')
                    ->on('contract')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('purchasing_processes');
    }
}
