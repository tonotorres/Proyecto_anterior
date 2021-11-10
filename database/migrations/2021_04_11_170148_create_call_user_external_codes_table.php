<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCallUserExternalCodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('call_user_external_codes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('call_user_id')->unsigned();
            $table->string('code', 36);
            $table->timestamps();

            $table->foreign('call_user_id')->references('id')->on('call_users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('call_user_external_codes');
    }
}
