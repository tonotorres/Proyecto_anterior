<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTemplateModulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_template_modules', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_template_id')->unsigned();
            $table->integer('module_id')->unsigned();
            $table->string('name', 50);
            $table->boolean('create')->default('0');
            $table->boolean('read')->default('0');
            $table->boolean('update')->default('0');
            $table->boolean('delete')->default('0');
            $table->timestamps();

            $table->foreign('user_template_id')->on('user_templates')->references('id')->onDelete('cascade');
            $table->foreign('module_id')->on('modules')->references('id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_template_modules');
    }
}
