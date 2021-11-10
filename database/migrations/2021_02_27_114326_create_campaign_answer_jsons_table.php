<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignAnswerJsonsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_answer_jsons', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('campaign_answer_id')->unsigned();
            $table->json('fields');
            $table->timestamps();

            $table->foreign('campaign_answer_id')->references('id')->on('campaign_answers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaign_answer_jsons');
    }
}
