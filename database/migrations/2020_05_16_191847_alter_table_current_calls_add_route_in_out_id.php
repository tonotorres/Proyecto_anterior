<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableCurrentCallsAddRouteInOutId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('current_calls', function (Blueprint $table) {
            $table->integer('route_in_id')->unsigned()->nullable()->after('call_end_id');
            $table->integer('route_out_id')->unsigned()->nullable()->after('route_in_id');

            $table->foreign('route_in_id')->references('id')->on('route_ins')->onDelete('set null');
            $table->foreign('route_out_id')->references('id')->on('route_outs')->onDelete('set null');
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
            $table->dropForeign('current_calls_route_in_id_foreign');
            $table->dropForeign('current_calls_route_out_id_foreign');

            $table->dropColumn('route_in_id');
            $table->dropColumn('route_out_id');
        });
    }
}
