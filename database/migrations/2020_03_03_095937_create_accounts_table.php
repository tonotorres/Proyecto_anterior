<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('company_id')->unsigned();
            $table->integer('account_type_id')->unsigned()->nullable();
            $table->string('code', 32)->nullable();
            $table->string('name', 100);
            $table->string('corporate_name', 150)->nullable();
            $table->string('vat_number', 32)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('account_type_id')->references('id')->on('account_types')->onDelete('cascade');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->integer('account_id')->unsigned()->nullable()->after('company_id');

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->integer('account_id')->unsigned()->nullable()->after('company_id');

            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
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
            $table->dropForeign('users_account_id_foreign');
            $table->dropColumn('account_id');
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign('contacts_account_id_foreign');
            $table->dropColumn('account_id');
        });

        Schema::dropIfExists('accounts');
    }
}
