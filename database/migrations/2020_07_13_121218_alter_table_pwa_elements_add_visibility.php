<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTablePwaElementsAddVisibility extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pwa_elements', function (Blueprint $table) {
            $table->tinyInteger('visibility')->unsigned()->default('0')->after('is_private');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pwa_elements', function (Blueprint $table) {
            $table->dropColumn('visibility');
        });
    }
}
