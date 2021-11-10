<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id')->unsigned();
            $table->string('name', 50);
            $table->string('color', 7);
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });

        Schema::create('tag_module', function (Blueprint $table) {
            $table->integer('tag_id')->unsigned();
            $table->integer('module_key')->unsigned();
            $table->integer('reference_id')->unsigned();

            $table->foreign('tag_id')->references('id')->on('tags')->onDelete('cascade');
        });

        Schema::table('list_contact_types', function (Blueprint $table) {
            $table->string('tags', 255)->nullable()->after('reference_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('list_contact_types', function (Blueprint $table) {
            $table->dropColumn('tags');
        });

        Schema::dropIfExists('tag_module');
        Schema::dropIfExists('tags');
    }
}
