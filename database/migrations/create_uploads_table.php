<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('uploads')) {
            return;
        }

        Schema::create('uploads', function (Blueprint $table) {
            $table->id();
            $table->string('uploadable_type');
            $table->unsignedBigInteger('uploadable_id');
            $table->text('name');
            $table->text('original_name');
            $table->string('type');
            $table->string('path');
            $table->string('extension');
            $table->string('size');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('uploads');
    }
};
