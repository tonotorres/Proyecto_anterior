<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCurrentCallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('current_calls', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id')->unsigned();
            $table->integer('call_type_id')->unsigned();
            $table->integer('call_status_id')->unsigned();
            $table->integer('call_end_id')->unsigned()->nullable();
            $table->integer('department_id')->unsigned()->nullable();
            $table->integer('account_id')->unsigned()->nullable();
            $table->integer('contact_id')->unsigned()->nullable();
            $table->string('from', 32);
            $table->string('to', 32);
            $table->string('queue', 16)->nullable();
            $table->string('uniqueid', 32);
            $table->string('linkedid', 32);
            $table->integer('start')->nullable();
            $table->integer('duration_wait')->nullable();
            $table->integer('duration')->nullable();
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('call_type_id')->references('id')->on('call_types')->onDelete('cascade');
            $table->foreign('call_status_id')->references('id')->on('call_statuses')->onDelete('cascade');
            $table->foreign('call_end_id')->references('id')->on('call_ends')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('current_calls');
    }
}
