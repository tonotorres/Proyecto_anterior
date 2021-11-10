<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddressBookOptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('address_book_options', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('address_book_id')->unsigned();
            $table->integer('pbx_ivr_id')->unsigned();
            $table->string('ddi', 50)->nullable();
            $table->string('option', 10);
            $table->string('overflow', 10);
            $table->timestamps();

            $table->foreign('address_book_id')->references('id')->on('address_books')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('address_book_options');
    }
}
