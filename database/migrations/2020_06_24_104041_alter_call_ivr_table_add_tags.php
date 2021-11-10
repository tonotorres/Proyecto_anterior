<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterCallIvrTableAddTags extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('call_ivrs', function (Blueprint $table) {
            $table->integer('ivr_tag_id')->unsigned()->nullable()->after('call_id');
            $table->integer('ivr_option_tag_id')->unsigned()->nullable()->after('ivr_tag_id');

            $table->foreign('ivr_tag_id')->references('id')->on('tags')->onDelete('set null');
            $table->foreign('ivr_option_tag_id')->references('id')->on('tags')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('call_ivrs', function (Blueprint $table) {
            $table->dropForeign('call_ivrs_ivr_tag_id_foreign');
            $table->dropForeign('call_ivrs_ivr_option_tag_id_foreign');
            $table->dropColumn('ivr_tag_id');
            $table->dropColumn('ivr_option_tag_id');
        });
    }
}
