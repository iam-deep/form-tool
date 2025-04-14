<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'userId')) {
                $table->renameColumn('id', 'userId');
                $table->renameColumn('created_at', 'createdAt');
                $table->renameColumn('updated_at', 'updatedAt');

                $table->integer('groupId')->after('password');
                $table->boolean('status')->after('remember_token');

                $table->integer('updatedBy')->nullable()->after('status');
                $table->integer('createdBy')->nullable()->after('updatedBy');
                $table->integer('deletedBy')->nullable()->after('createdBy');
                $table->datetime('deletedAt')->nullable()->after('deletedBy');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    // public function down()
    // {
    //     Schema::table('users', function (Blueprint $table) {
    //         $table->renameColumn('userId', 'id');
    //         $table->renameColumn('createdAt', 'created_at');
    //         $table->renameColumn('updatedAt', 'updated_at');

    //         $table->dropColumn('groupId');
    //         $table->dropColumn('status');

    //         $table->dropColumn('updatedBy');
    //         $table->dropColumn('createdBy');
    //         $table->dropColumn('deletedBy');
    //         $table->dropColumn('deletedAt');
    //     });
    // }
}
