<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContactCoursesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contact_courses', function (Blueprint $table) {
            $table->id();

            $table->string('woocommerce_course_id_crm');
        
            $table->foreignId('contacts_id_fk')
                ->references('id')
                ->on('contacts');

            // $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contact_courses');
    }
}
