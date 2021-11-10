<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableChatRooms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('chat_rooms', function (Blueprint $table) {
            $table->string('name', 255)->change();
            $table->integer('account_id')->unsigned()->nullable()->after('message_type_id');
            $table->string('from', 36)->after('name');
            $table->string('to', 36)->after('from');
            $table->softDeletes();

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('chat_rooms', function (Blueprint $table) {
            $table->dropForeign('chat_rooms_account_id_foreign');
            $table->string('name', 100)->change();
            $table->dropColumn('account_id')->unsigned()->nullable();
            $table->dropColumn('from');
            $table->dropColumn('to');
            $table->dropColumn('deleted_at');
        });
    }
}
