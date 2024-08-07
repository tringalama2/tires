<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rotations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tire_id');
            $table->unsignedTinyInteger('starting_position');
            $table->date('rotated_on');
            $table->unsignedMediumInteger('starting_odometer');
            $table->unsignedTinyInteger('starting_tread')->comment('in 32nds on an inch');

            $table->timestamps();

            $table->foreign('tire_id')->references('id')->on('tires');
        });
    }

};
