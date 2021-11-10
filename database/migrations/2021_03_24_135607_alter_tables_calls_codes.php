<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTablesCallsCodes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('call_types', function (Blueprint $table) {
            $table->string('code', 36)->nullable()->after('name');
        });

        Schema::table('call_statuses', function (Blueprint $table) {
            $table->string('code', 36)->nullable()->after('name');
        });

        Schema::table('call_ends', function (Blueprint $table) {
            $table->string('code', 36)->nullable()->after('position');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('call_types', function (Blueprint $table) {
            $table->dropColumn('code');
        });

        Schema::table('call_statuses', function (Blueprint $table) {
            $table->dropColumn('code');
        });

        Schema::table('call_ends', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
}
