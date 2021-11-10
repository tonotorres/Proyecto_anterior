<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableUserAddStartBreakTimeId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('signin_break_time_id')->unsigned()->nullable()->after('account_id');
            $table->foreign('signin_break_time_id')->references('id')->on('break_times')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_signin_break_time_id_foreign');
            $table->dropColumn('signin_break_time_id');
        });
    }
}
