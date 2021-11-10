<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportDurationCallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('report_duration_calls', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id')->unsigned();
            $table->mediumInteger('year')->unsigned();
            $table->smallInteger('month')->unsigned();
            $table->smallInteger('day')->unsigned();
            $table->smallInteger('hour')->unsigned();
            $table->integer('call_type_id')->unsigned();
            $table->integer('call_status_id')->unsigned();
            $table->integer('call_end_id')->unsigned();
            $table->integer('ddi_id')->unsigned();
            $table->integer('range')->unsigned();
            $table->integer('duration')->unsigned();

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
        Schema::dropIfExists('report_duration_calls');
    }
}
