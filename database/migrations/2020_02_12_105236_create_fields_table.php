<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFieldsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fields', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('section_id')->unsigned();
            $table->integer('field_type_id')->unsigned();
            $table->string('key', 50);
            $table->string('width', 32);
            $table->string('label', 100)->nullable();
            $table->string('name', 50);
            $table->string('default', 100)->nullable();
            $table->string('validations_create', 255)->default('nullable');
            $table->string('validations_update', 255)->default('nullable');
            $table->text('options')->nullable();
            $table->integer('position')->unsigned();
            $table->boolean('is_simple_form')->default('0');
            $table->timestamps();

            $table->foreign('section_id')->on('sections')->references('id')->onDelete('cascade');
            $table->foreign('field_type_id')->on('field_types')->references('id')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fields');
    }
}
