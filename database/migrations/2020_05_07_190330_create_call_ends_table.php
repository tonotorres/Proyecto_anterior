<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallEndsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('call_ends', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id')->unsigned();
            $table->string('name', 50);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        Schema::create('call_end_call_type', function (Blueprint $table) {
            $table->integer('call_end_id')->unsigned();
            $table->integer('call_type_id')->unsigned();

            $table->foreign('call_end_id')->references('id')->on('call_ends')->onDelete('cascade');
            $table->foreign('call_type_id')->references('id')->on('call_types')->onDelete('cascade');
        });

        Schema::create('call_end_department', function (Blueprint $table) {
            $table->integer('call_end_id')->unsigned();
            $table->integer('department_id')->unsigned();

            $table->foreign('call_end_id')->references('id')->on('call_ends')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('call_end_department');
        Schema::dropIfExists('call_end_call_type');
        Schema::dropIfExists('call_ends');
    }
}
