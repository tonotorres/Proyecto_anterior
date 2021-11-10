<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignCallsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_calls', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('campaign_id')->unsigned()->nullable();
            $table->string('phone', 16);
            $table->string('name', 50)->nullable();
            $table->integer('weight')->default('1');
            $table->smallInteger('retries')->default('0');
            $table->smallInteger('total_retries')->default('1');
            $table->boolean('is_correct')->default('0');
            $table->boolean('is_paused')->default('0');
            $table->boolean('is_blocked')->default('0');
            $table->boolean('is_ringing')->default('0');
            $table->timestamps();

            $table->foreign('campaign_id')->references('id')->on('campaigns')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campaign_calls');
    }
}
