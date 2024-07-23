<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up() : void
    {
        Schema::table('uploads', function (Blueprint $table) {
            $table->string('collection')->nullable()->default(null)->after('size');
            $table->string('disk')->nullable()->default(null)->after('collection');
        });
    }

    public function down() : void
    {
        Schema::table('uploads', function (Blueprint $table) {
            $table->dropColumn('collection');
            $table->dropColumn('disk');
        });
    }
};
