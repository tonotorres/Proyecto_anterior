<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePbxChannelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pbx_bridges', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 36);
            $table->timestamps();
        });

        Schema::create('pbx_channel_states', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('key');
            $table->string('name', 50);
            $table->timestamps();
        });

        Schema::create('pbx_channels', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('pbx_channel_state_id')->unsigned();
            $table->integer('current_call_id')->unsigned()->nullable();
            $table->integer('pbx_bridge_id')->unsigned()->nullable();
            $table->string('name', 50);
            $table->string('callerid', 32)->nullable();
            $table->string('uniqueid', 32);
            $table->string('linkedid', 32);
            $table->timestamps();

            $table->foreign('pbx_channel_state_id')->references('id')->on('pbx_channel_states')->onDelete('cascade');
            $table->foreign('current_call_id')->references('id')->on('current_calls')->onDelete('cascade');
            $table->foreign('pbx_bridge_id')->references('id')->on('pbx_bridges')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pbx_channels');
        Schema::dropIfExists('pbx_channel_states');
        Schema::dropIfExists('pbx_bridges');
    }
}
