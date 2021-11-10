<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableCallCurrentCall extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('call_current_call', function (Blueprint $table) {
            $table->integer('call_id')->unsigned();
            $table->integer('current_call_id')->unsigned();
            
            $table->foreign('call_id')->references('id')->on('calls')->onDelete('cascade');
            $table->index('current_call_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('call_current_call');
    }
}
