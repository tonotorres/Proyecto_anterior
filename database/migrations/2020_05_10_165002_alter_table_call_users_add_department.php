<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableCallUsersAddDepartment extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('current_call_users', function (Blueprint $table) {
            $table->integer('department_id')->unsigned()->nullable()->after('user_id');
            $table->foreign('department_id', 'ccu_department_id_fk')->references('id')->on('departments')->onDelete('set null');
        });

        Schema::table('current_call_user_calleds', function (Blueprint $table) {
            $table->integer('department_id')->unsigned()->nullable()->after('user_id');
            $table->foreign('department_id', 'ccuc_department_id_fk')->references('id')->on('departments')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('current_call_users', function (Blueprint $table) {
            $table->dropForeign('ccu_department_id_fk');
            $table->dropColumn('department_id');
        });
        
        Schema::table('current_call_user_calleds', function (Blueprint $table) {
            $table->dropForeign('ccuc_department_id_fk');
            $table->dropColumn('department_id');
        });
    }
}
