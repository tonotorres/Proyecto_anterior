<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableUserChatRoom extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_chat_room', function (Blueprint $table) {
            $table->string('name', 255)->change();
            $table->timestamp('last_connection_at')->nullable();
            $table->timestamp('last_read_message_id')->nullable();
            $table->boolean('is_online')->default('0');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_chat_room', function (Blueprint $table) {
            $table->string('name', 100)->change();
            $table->dropColumn('last_connection_at');
            $table->dropColumn('last_read_message_id');
            $table->dropColumn('is_online');
        });
    }
}
