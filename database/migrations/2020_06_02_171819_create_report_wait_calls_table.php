<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportWaitCallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('report_wait_calls', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id')->unsigned();
            $table->mediumInteger('year')->unsigned();
            $table->smallInteger('month')->unsigned();
            $table->smallInteger('day')->unsigned();
            $table->smallInteger('hour')->unsigned();
            $table->integer('call_status_id')->unsigned();
            $table->integer('call_end_id')->unsigned();
            $table->integer('ddi_id')->unsigned();
            $table->smallInteger('range')->unsigned();
            $table->integer('total')->unsigned();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('report_wait_calls');
    }
}
