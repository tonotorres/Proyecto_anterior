<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableCampaignAnswerAddEnd extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('campaign_answers', function (Blueprint $table) {
            $table->integer('campaign_answer_end_id')->unsigned()->nullable()->after('campaign_contact_id');
            $table->foreign('campaign_answer_end_id')->references('id')->on('campaign_answer_ends')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('campaign_answers', function (Blueprint $table) {
            $table->dropForeign('campaign_answers_campaign_answer_end_id_foreign');
            $table->dropColumn('campaign_answer_end_id');
        });
    }
}
