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
            $table->integer('step',false, true);
            $table->string('country')->nullable();
            $table->string('title')->nullable();

/*             $table->foreignId('country_id_fk')
                    ->nullable()        
                    ->references('id')
                    ->on('countries');
            $table->foreignId('lead_id_fk')
                    ->nullable()        
                    ->references('id')
                    ->on('leads');
 */
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
