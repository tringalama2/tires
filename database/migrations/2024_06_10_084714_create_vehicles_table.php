<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedSmallInteger('year');
            $table->string('make', 50);
            $table->string('model', 50);
            $table->string('vin', 17);
            $table->string('nickname', 50);
            $table->unsignedTinyInteger('tire_count')->default(5);
            $table->timestamp('last_selected_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
        });
    }
};
