<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepartmentConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('department_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('department_id')->unsigned();
            $table->integer('department_config_group_id')->unsigned();
            $table->string('key', '50');
            $table->string('label', '50');
            $table->integer('position');
            $table->text('value');
            $table->timestamps();

            $table->foreign('department_id')->on('departments')->references('id')->onDelete('cascade');
            $table->foreign('department_config_group_id')->on('department_config_groups')->references('id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('department_configs');
    }
}
