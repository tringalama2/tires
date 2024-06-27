<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rotation_tire', function (Blueprint $table) {
            $table->uuid('rotation_id');
            $table->uuid('tire_id');
            $table->unsignedTinyInteger('position');
            $table->unsignedTinyInteger('tread')->comment('in 32nds on an inch');
            $table->timestamps();

            $table->primary(['rotation_id', 'tire_id']);

            $table->foreign('rotation_id')->references('id')->on('rotations');
            $table->foreign('tire_id')->references('id')->on('tires');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rotation_tire');
    }
};
