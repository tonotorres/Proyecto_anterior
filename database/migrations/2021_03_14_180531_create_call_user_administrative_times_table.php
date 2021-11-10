<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallUserAdministrativeTimesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('call_user_administrative_times', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('call_id')->unsigned();
            $table->enum('call_type', ['current_call', 'call'])->default('current_call');
            $table->integer('user_id')->unsigned();
            $table->integer('duration')->unsigned()->default('60');
            $table->boolean('is_started')->default('0');
            $table->timestamps();

            $table->index('call_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('call_user_administrative_times');
    }
}
