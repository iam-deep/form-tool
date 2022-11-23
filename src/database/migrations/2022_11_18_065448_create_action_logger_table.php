<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActionLoggerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! \config('form-tool.isLogActions')) {
            return;
        }

        Schema::create('action_logs', function (Blueprint $table) {
            $table->id();
            $table->string('action')->index();
            $table->string('module')->nullable();
            $table->string('route')->nullable();
            $table->string('refId')->nullable()->index();
            $table->string('token')->nullable()->index();
            $table->mediumText('data')->nullable();         // MediumText or higher is recommended as there may be multiple EditorType data
            $table->integer('actionBy')->nullable();
            $table->string('actionByName')->nullable();
            $table->dateTime('actionAt')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! \config('form-tool.isLogActions')) {
            return;
        }

        Schema::dropIfExists('action_logs');
    }
}
