<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBreakTimeUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('break_time_users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('break_time_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->bigInteger('start')->unsigned();
            $table->bigInteger('end')->unsigned()->nullable();
            $table->integer('duration')->unsigned()->default('0');
            $table->timestamps();

            $table->foreign('break_time_id')->references('id')->on('break_times')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('break_time_users');
    }
}
