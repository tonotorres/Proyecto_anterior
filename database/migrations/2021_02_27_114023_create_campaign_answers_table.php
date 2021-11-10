<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignAnswersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_answers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('campaign_id')->unsigned();
            $table->integer('campaign_form_id')->unsigned();
            $table->integer('campaign_contact_id')->unsigned();
            $table->boolean('is_correct')->default('0');
            $table->boolean('is_forced_correct')->default('0');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
            $table->foreign('campaign_form_id')->references('id')->on('campaign_forms')->onDelete('cascade');
            $table->foreign('campaign_contact_id')->references('id')->on('campaign_contacts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaign_answers');
    }
}
