<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rotations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vehicle_id');
            $table->date('rotated_on');
            $table->unsignedMediumInteger('odometer');
            $table->text('note')->nullable();
            $table->boolean('is_setup')->default(false)->comment('true for the initial tire-install event, not a real rotation');
            $table->boolean('is_swap')->default(false);
            $table->timestamps();

            $table->foreign('vehicle_id')->references('id')->on('vehicles')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rotations');
    }
};
