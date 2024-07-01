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
            $table->unsignedBigInteger('vehicle_id');
            $table->date('rotated_on');
            $table->unsignedMediumInteger('odometer');
            $table->timestamps();

            $table->foreign('vehicle_id')->references('id')->on('vehicles');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rotations');
    }
};
