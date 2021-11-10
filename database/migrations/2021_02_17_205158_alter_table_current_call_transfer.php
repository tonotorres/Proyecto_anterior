<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableCurrentCallTransfer extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('current_call_transfers', function (Blueprint $table) {
            $table->string('from', 32)->change();
            $table->string('to', 32)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('current_call_transfers', function (Blueprint $table) {
            $table->string('from', 8)->change();
            $table->string('to', 8)->change();
        });
    }
}
