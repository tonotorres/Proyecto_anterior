<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskTimeDescriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_time_descriptions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('task_time_id')->unsigned();
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->foreign('task_time_id')->references('id')->on('task_times')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('task_time_descriptions');
    }
}
