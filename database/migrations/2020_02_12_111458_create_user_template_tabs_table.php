<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTemplateTabsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_template_tabs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_template_module_id')->unsigned();
            $table->integer('tab_id')->unsigned()->nullable();
            $table->string('name', 50);
            $table->integer('position')->unsigned();
            $table->timestamps();

            $table->foreign('user_template_module_id')->on('user_template_modules')->references('id')->onDelete('cascade');
            $table->foreign('tab_id')->on('tabs')->references('id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_template_tabs');
    }
}
