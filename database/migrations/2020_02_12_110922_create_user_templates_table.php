<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_templates', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id')->unsigned()->nullable();
            $table->string('name', 50);
            $table->integer('weight')->unsigned();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->on('companies')->references('id')->onDelete('cascade');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('user_template_id')->unsigned()->nullable()->after('id');
            $table->foreign('user_template_id')->on('user_templates')->references('id')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign('users_user_template_id_foreign');
            $table->dropColumn('user_template_id');
        });

        Schema::dropIfExists('user_templates');
    }
}
