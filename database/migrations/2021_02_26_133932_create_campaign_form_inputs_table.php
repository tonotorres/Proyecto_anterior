<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignFormInputsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_form_inputs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('campaign_form_id')->unsigned();
            $table->string('type', 16);
            $table->string('label', 100)->nullable();
            $table->string('name', 36);
            $table->integer('position')->unsigned();
            $table->boolean('is_required')->default(0);
            $table->integer('min')->nullable();
            $table->integer('max')->nullable();
            $table->integer('step')->nullable();
            $table->text('options')->nullable();
            $table->timestamps();

            $table->foreign('campaign_form_id')->references('id')->on('campaign_forms')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaign_form_inputs');
    }
}
