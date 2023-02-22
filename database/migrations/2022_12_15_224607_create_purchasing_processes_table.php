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
            $table->string('country')->nullable()->default(null);
            $table->foreignId('lead_id')
                    ->nullable()->default(null)
                    ->references('id')
                    ->on('leads')->onDelete('cascade')->onUpdate('cascade');

            $table->foreignId('contact_id')
                    ->nullable()->default(null)
                    ->references('id')
                    ->on('contacts')->onDelete('cascade')->onUpdate('cascade');

            $table->foreignId('contract_id')
                    ->nullable()->default(null)
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
        Schema::dropIfExists('purchasing_processes');
    }
}
