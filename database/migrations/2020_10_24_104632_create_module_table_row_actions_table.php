<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateModuleTableRowActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('module_table_row_actions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('module_table_row_id')->unsigned();
            $table->string('name', 32);
            $table->string('action', 100);
            $table->timestamps();

            $table->foreign('module_table_row_id')->references('id')->on('module_table_rows')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('module_table_row_actions');
    }
}
