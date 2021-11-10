<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExtensionStatusLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('extension_status_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('extension_id')->unsigned();
            $table->integer('extension_status_id')->unsigned();
            $table->timestamps();

            $table->foreign('extension_id')->references('id')->on('extensions')->onDelete('cascade');
            $table->foreign('extension_status_id')->references('id')->on('extension_statuses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('extension_status_logs');
    }
}
