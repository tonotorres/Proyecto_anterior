<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_types', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id')->unsigned()->nullable();
            $table->string('name', 50);
            $table->integer('weight')->default('0');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();

            $table->foreign('company_id')->on('companies')->references('id')->onDelete('cascade');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('user_type_id')->unsigned()->nullable()->after('id');
            $table->foreign('user_type_id')->on('user_types')->references('id')->onDelete('set null');
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
            $table->dropForeign('users_user_type_id_foreign');
            $table->dropColumn('user_type_id');
        });

        Schema::dropIfExists('user_types');
    }
}
