<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableCallsAddCampaigns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('current_calls', function (Blueprint $table) {
            $table->dropForeign('current_calls_contact_id_foreign');
            $table->dropColumn('contact_id');

            $table->integer('campaign_id')->unsigned()->nullable()->after('account_id');
            $table->integer('campaign_contact_id')->unsigned()->nullable()->after('campaign_id');

            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->foreign('campaign_contact_id')->references('id')->on('campaign_contacts')->onDelete('cascade');
        });

        Schema::table('calls', function (Blueprint $table) {
            $table->dropForeign('calls_contact_id_foreign');
            $table->dropColumn('contact_id');

            $table->integer('campaign_id')->unsigned()->nullable()->after('account_id');
            $table->integer('campaign_contact_id')->unsigned()->nullable()->after('campaign_id');

            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->foreign('campaign_contact_id')->references('id')->on('campaign_contacts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('current_calls', function (Blueprint $table) {
            $table->dropForeign('current_calls_campaign_id_foreign');
            $table->dropForeign('current_calls_campaign_contact_id_foreign');
            $table->dropColumn('campaign_id');
            $table->dropColumn('campaign_contact_id');

            $table->integer('contact_id')->unsigned()->nullable()->after('account_id');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });

        Schema::table('calls', function (Blueprint $table) {
            $table->dropForeign('calls_campaign_id_foreign');
            $table->dropForeign('calls_campaign_contact_id_foreign');
            $table->dropColumn('campaign_id');
            $table->dropColumn('campaign_contact_id');

            $table->integer('contact_id')->unsigned()->nullable()->after('account_id');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }
}
