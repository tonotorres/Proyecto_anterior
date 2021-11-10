<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_sessions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id')->unsigned();
            $table->integer('user_id')->unsigned();
            $table->timestamp('start')->nullable();
            $table->timestamp('end')->nullable();
            $table->integer('duration')->unsigned()->nullable();
            $table->string('ip_start', 32)->nullable();
            $table->string('ip_end', 32)->nullable();
            $table->string('latitude_start', 32)->nullable();
            $table->string('longitude_start', 32)->nullable();
            $table->string('latitude_end', 32)->nullable();
            $table->string('longitude_end', 32)->nullable();
            $table->boolean('ip_error')->default('0');
            $table->boolean('coord_error')->default('0');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
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
        Schema::dropIfExists('user_sessions');
    }
}
