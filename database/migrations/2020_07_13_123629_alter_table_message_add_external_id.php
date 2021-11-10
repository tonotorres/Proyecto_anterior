<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableMessageAddExternalId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('chat_rooms', function (Blueprint $table) {
            $table->string('externalid')->nullable()->after('message_type_id');
            $table->index('externalid');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->string('externalid')->nullable()->after('contact_id');
            $table->index('externalid');
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
            $table->dropIndex('chat_rooms_externalid_index');
            $table->dropColumn('externalid');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_externalid_index');
            $table->dropColumn('externalid');
        });
    }
}
