<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatRoomsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chat_rooms', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id')->unsigned();
            $table->integer('message_type_id')->unsigned();
            $table->string('name', 100)->nullable();
            $table->boolean('is_active')->default('0');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('message_type_id')->references('id')->on('message_types')->onDelete('cascade');
        });

        Schema::create('department_chat_room', function (Blueprint $table) {
            $table->integer('department_id')->unsigned();
            $table->integer('chat_room_id')->unsigned();

            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('chat_room_id')->references('id')->on('chat_rooms')->onDelete('cascade');
        });

        Schema::create('user_chat_room', function (Blueprint $table) {
            $table->integer('user_id')->unsigned();
            $table->integer('chat_room_id')->unsigned();
            $table->string('name', 100);
            $table->smallInteger('unread')->default('0');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('chat_room_id')->references('id')->on('chat_rooms')->onDelete('cascade');
        });

        Schema::create('contact_chat_room', function (Blueprint $table) {
            $table->integer('contact_id')->unsigned();
            $table->integer('chat_room_id')->unsigned();
            $table->string('name', 100);
            $table->smallInteger('unread')->default('0');

            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('chat_room_id')->references('id')->on('chat_rooms')->onDelete('cascade');
        });

        Schema::create('account_chat_room', function (Blueprint $table) {
            $table->integer('account_id')->unsigned();
            $table->integer('chat_room_id')->unsigned();
            $table->string('name', 100);
            $table->smallInteger('unread')->default('0');

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('chat_room_id')->references('id')->on('chat_rooms')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('department_chat_room');
        Schema::dropIfExists('user_chat_room');
        Schema::dropIfExists('contact_chat_room');
        Schema::dropIfExists('account_chat_room');
        Schema::dropIfExists('chat_rooms');
    }
}
