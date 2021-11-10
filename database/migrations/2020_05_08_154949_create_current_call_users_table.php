<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrentCallUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('current_call_users', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('current_call_id')->unsigned();
            $table->integer('user_id')->unsigned()->nullable();
            $table->string('extension', 16);
            $table->integer('start')->nullable();
            $table->integer('duration')->nullable();

            $table->foreign('current_call_id')->references('id')->on('current_calls')->onDelete('cascade');
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
        Schema::dropIfExists('current_call_users');
    }
}
