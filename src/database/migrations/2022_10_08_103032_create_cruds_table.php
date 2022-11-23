<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrudsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! \config('form-tool.isPreventForeignKeyDelete')) {
            return;
        }

        Schema::create('cruds', function (Blueprint $table) {
            $table->id();
            $table->string('route');
            $table->text('data');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! \config('form-tool.isPreventForeignKeyDelete')) {
            return;
        }

        Schema::dropIfExists('cruds');
    }
}
