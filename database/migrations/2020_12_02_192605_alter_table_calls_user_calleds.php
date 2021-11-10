<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableCallsUserCalleds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('current_call_user_calleds', function (Blueprint $table) {
            $table->boolean('answered')->default('0')->after('start');
        });

        Schema::table('call_user_calleds', function (Blueprint $table) {
            $table->boolean('answered')->default('0')->after('start');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('current_call_user_calleds', function (Blueprint $table) {
            $table->dropColumn('answered');
        });

        Schema::table('call_user_calleds', function (Blueprint $table) {
            $table->dropColumn('answered');
        });
    }
}
