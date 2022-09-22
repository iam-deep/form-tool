<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('userId');
            $table->string('name');
            $table->string('email')->unique();
            $table->integer('groupId');
            $table->timestamp('emailVerifiedAt')->nullable();
            $table->string('password');
            $table->boolean('status');

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
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
