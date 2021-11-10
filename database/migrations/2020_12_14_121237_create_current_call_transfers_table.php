<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrentCallTransfersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('current_call_transfers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('current_call_id')->unsigned();
            $table->string('from', 8);
            $table->string('to', 8);
            $table->timestamps();

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
        Schema::dropIfExists('current_call_transfers');
    }
}
