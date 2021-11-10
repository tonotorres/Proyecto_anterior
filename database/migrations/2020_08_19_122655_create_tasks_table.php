<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id')->unsigned();
            $table->integer('task_type_id')->unsigned();
            $table->integer('task_priority_id')->unsigned();
            $table->integer('task_status_id')->unsigned();
            $table->integer('task_list_id')->unsigned()->nullable();
            $table->integer('task_id')->unsigned()->nullable();
            $table->string('name', 255);
            $table->integer('start')->unsigned();
            $table->integer('end')->unsigned()->nullable();
            $table->integer('duration')->unsigned()->default('0');
            $table->integer('finish')->unsigned()->default('0');
            $table->boolean('billable')->default('0');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('task_type_id')->references('id')->on('task_types')->onDelete('cascade');
            $table->foreign('task_priority_id')->references('id')->on('task_priorities')->onDelete('cascade');
            $table->foreign('task_status_id')->references('id')->on('task_statuses')->onDelete('cascade');
            $table->foreign('task_list_id')->references('id')->on('task_lists')->onDelete('cascade');
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
        });

        Schema::create('task_user', function (Blueprint $table) {
            $table->integer('task_id')->unsigned();
            $table->integer('user_id')->unsigned();

            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('task_user');
        Schema::dropIfExists('tasks');
    }
}
