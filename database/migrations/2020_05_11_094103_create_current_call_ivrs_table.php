<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrentCallIvrsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('current_call_ivrs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('current_call_id')->unsigned();
            $table->integer('pbx_ivr');
            $table->string('option', 4)->nullable();
            $table->integer('start')->unsigned();

            $table->foreign('current_call_id')->references('id')->on('current_calls')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('current_call_ivrs');
    }
}
