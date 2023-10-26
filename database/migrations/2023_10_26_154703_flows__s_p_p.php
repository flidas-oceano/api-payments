<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FlowsSPP extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('flows_spp', function (Blueprint $table) {
            $table->id();
            $table->string('contract_id')->nullable()->default(null);
            $table->string('contract_so')->nullable()->default(null);
            $table->string('reference')->nullable()->default(null);
            $table->json('zohoData')->nullable()->default(null);

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
        Schema::dropIfExists('flows_spp');
    }
}
