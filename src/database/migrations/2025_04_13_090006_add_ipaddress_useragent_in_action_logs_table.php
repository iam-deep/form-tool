<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('action_logs', function (Blueprint $table) {
            $table->string('ipAddress')->nullable()->after('data');
            $table->string('userAgent', 255)->nullable()->after('ipAddress');
        });
    }
};
