<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_groups', function (Blueprint $table) {
            $table->id('groupId');
            $table->string('groupName');
            $table->text('permission')->nullable();

            $table->integer('updatedBy')->nullable();
            $table->datetime('updatedAt')->nullable();
            $table->integer('createdBy')->nullable();
            $table->datetime('createdAt');
            $table->integer('deletedBy')->nullable();
            $table->datetime('deletedAt')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    // public function down()
    // {
    //     Schema::dropIfExists('user_groups');
    // }
}
