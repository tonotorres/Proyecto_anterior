<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExtensionStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('extension_statuses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 32);
            $table->timestamps();
        });

        Schema::table('extensions', function (Blueprint $table) {
            $table->integer('extension_status_id')->unsigned()->nullable()->after('department_id');
            $table->foreign('extension_status_id')->references('id')->on('extension_statuses')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('extensions', function (Blueprint $table) {
            $table->dropForeign('extensions_extension_status_id_foreign');
            $table->dropColumn('extension_status_id');
        });

        Schema::dropIfExists('extension_statuses');
    }
}
