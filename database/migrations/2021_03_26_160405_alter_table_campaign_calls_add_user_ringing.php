<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableCampaignCallsAddUserRinging extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaign_calls', function (Blueprint $table) {
            $table->dropColumn('is_ringing');
            $table->integer('ringing_user_id')->unsigned()->nullable()->after('is_blocked');

            $table->foreign('ringing_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campaign_calls', function (Blueprint $table) {
            $table->dropForeign('campaign_calls_ringing_user_id_foreign');
            $table->dropColumn('ringing_user_id');
            $table->boolean('is_ringing')->default('0')->after('is_blocked');
        });
    }
}
