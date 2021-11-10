<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessageBodiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('message_bodies', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('message_body_type_id')->unsigned();
            $table->integer('message_id')->unsigned();
            $table->text('content');
            $table->timestamps();

            $table->foreign('message_body_type_id')->references('id')->on('message_body_types')->onDelete('cascade');
            $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('message_bodies');
    }
}
