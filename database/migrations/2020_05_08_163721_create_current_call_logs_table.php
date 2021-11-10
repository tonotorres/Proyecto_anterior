<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrentCallLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('current_call_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('current_call_id')->unsigned();
            $table->integer('call_log_type_id')->unsigned();
            $table->string('reference_id', 32)->nullable();
            $table->string('description', 100);
            $table->integer('start')->nullable();
            

            $table->foreign('current_call_id')->references('id')->on('current_calls')->onDelete('cascade');
            $table->foreign('call_log_type_id')->references('id')->on('call_log_types')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('current_call_logs');
    }
}
