<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDepartmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id')->unsigned();
            $table->integer('department_id')->unsigned()->nullable();
            $table->string('code', 32)->nullable();
            $table->string('name', 50);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->on('companies')->references('id')->onDelete('cascade');
            $table->foreign('department_id')->on('departments')->references('id')->onDelete('cascade');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('department_id')->unsigned()->nullable()->after('id');
            $table->foreign('department_id')->on('departments')->references('id')->onDelete('set null');
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
            $table->dropForeign('users_department_id_foreign');
            $table->dropColumn('department_id');
        });

        Schema::dropIfExists('departments');
    }
}
