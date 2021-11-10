<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTablesAddAdministrativeTime extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->integer('administrative_time')->unsigned()->nullable()->after('name');
        });

        Schema::table('campaign_in_calls', function (Blueprint $table) {
            $table->integer('administrative_time')->unsigned()->nullable()->after('queue');
        });

        Schema::table('campaign_out_calls', function (Blueprint $table) {
            $table->integer('administrative_time')->unsigned()->nullable()->after('end_time');
        });

        Schema::table('current_call_users', function (Blueprint $table) {
            $table->integer('administrative_time')->unsigned()->nullable()->after('duration');
        });

        Schema::table('current_calls', function (Blueprint $table) {
            $table->integer('administrative_time')->unsigned()->nullable()->after('duration');
        });

        Schema::table('call_users', function (Blueprint $table) {
            $table->integer('administrative_time')->unsigned()->nullable()->after('duration');
        });

        Schema::table('calls', function (Blueprint $table) {
            $table->integer('administrative_time')->unsigned()->nullable()->after('duration');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropColumn('administrative_time');
        });

        Schema::table('campaign_in_calls', function (Blueprint $table) {
            $table->dropColumn('administrative_time');
        });

        Schema::table('campaign_out_calls', function (Blueprint $table) {
            $table->dropColumn('administrative_time');
        });

        Schema::table('current_call_users', function (Blueprint $table) {
            $table->dropColumn('administrative_time');
        });

        Schema::table('current_calls', function (Blueprint $table) {
            $table->dropColumn('administrative_time');
        });

        Schema::table('call_users', function (Blueprint $table) {
            $table->dropColumn('administrative_time');
        });

        Schema::table('calls', function (Blueprint $table) {
            $table->dropColumn('administrative_time');
        });
    }
}
