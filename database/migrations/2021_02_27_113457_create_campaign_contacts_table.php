<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCampaignContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campaign_contacts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('campaign_id')->unsigned();
            $table->string('name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->date('birthday')->nullable();
            $table->string('nif', 36)->nullable();
            $table->string('phone_1', 36)->nullable();
            $table->string('phone_2', 36)->nullable();
            $table->string('email_1', 100)->nullable();
            $table->string('email_2', 100)->nullable();
            $table->string('address', 100)->nullable();
            $table->string('address_aux', 100)->nullable();
            $table->string('postal_code', 16)->nullable();
            $table->string('location', 50)->nullable();
            $table->string('region', 50)->nullable();
            $table->string('country', 50)->nullable();
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
        Schema::dropIfExists('campaign_contacts');
    }
}
