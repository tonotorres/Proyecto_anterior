<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIvrOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ivr_options', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('ivr_id')->unsigned();
            $table->integer('tag_id')->unsigned()->nullable();
            $table->string('option', 8);
            $table->timestamps();

            $table->foreign('ivr_id')->references('id')->on('ivrs')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ivr_options');
    }
}
