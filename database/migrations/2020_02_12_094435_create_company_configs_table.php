<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id')->unsigned();
            $table->integer('company_config_group_id')->unsigned();
            $table->string('key', '50');
            $table->string('label', '50');
            $table->integer('position');
            $table->text('value');
            $table->timestamps();

            $table->foreign('company_id')->on('companies')->references('id')->onDelete('cascade');
            $table->foreign('company_config_group_id')->on('company_config_groups')->references('id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company_configs');
    }
}
