<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddressBookDestinationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('address_book_destinations', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('address_book_id')->unsigned();
            $table->integer('module_id')->unsigned();
            $table->integer('reference_id')->unsigned();
            $table->string('destination', 10);
            $table->timestamps();

            $table->foreign('address_book_id')->references('id')->on('address_books')->onDelete('cascade');
            $table->foreign('module_id')->references('id')->on('modules')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('address_book_destinations');
    }
}
