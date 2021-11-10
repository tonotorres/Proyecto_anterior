<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableDepartmentChatRoom extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('department_chat_room', function (Blueprint $table) {
            $table->timestamp('last_connection_at')->nullable();
            $table->timestamp('last_read_message_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('department_chat_room', function (Blueprint $table) {
            $table->dropColumn('last_connection_at');
            $table->dropColumn('last_read_message_id');
        });
    }
}
