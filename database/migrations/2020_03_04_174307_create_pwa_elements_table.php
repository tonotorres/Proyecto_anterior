<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePwaElementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pwa_elements', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('pwa_page_id')->unsigned();
            $table->integer('pwa_element_type_id')->unsigned();
            $table->integer('pwa_language_id')->unsigned();
            $table->string('title', 50);
            $table->text('content');
            $table->integer('position')->unsigned();
            $table->boolean('is_private')->default('0');
            $table->timestamps();

            $table->foreign('pwa_page_id')->references('id')->on('pwa_pages')->onDelete('cascade');
            $table->foreign('pwa_element_type_id')->references('id')->on('pwa_element_types')->onDelete('cascade');
            $table->foreign('pwa_language_id')->references('id')->on('pwa_languages')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pwa_elements');
    }
}
