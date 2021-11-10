<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMessagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('chat_room_id')->unsigned();
            $table->integer('message_type_id')->unsigned();
            $table->integer('write_user_id')->unsigned()->nullable();
            $table->integer('read_user_id')->unsigned()->nullable();
            $table->integer('account_id')->unsigned()->nullable();
            $table->integer('contact_id')->unsigned()->nullable();
            $table->string('from', 100);
            $table->string('to', 100);
            $table->string('fromName', 100);
            $table->string('toName', 100);
            $table->string('subject', 100);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('chat_room_id')->references('id')->on('chat_rooms')->onDelete('cascade');
            $table->foreign('message_type_id')->references('id')->on('message_types')->onDelete('cascade');
            $table->foreign('write_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('read_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('set null');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('messages');
    }
}
