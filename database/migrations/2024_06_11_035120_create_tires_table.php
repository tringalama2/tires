<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tires', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('vehicle_id');
            $table->string('brand', 255)->nullable();
            $table->string('model', 255)->nullable();
            $table->string('label', 255);
            $table->string('tin', 12)->nullable()->comment('DOT tire identification number');
            $table->mediumText('desc')->nullable();
            $table->string('size', 255)->nullable();
            $table->date('purchased_on');
            $table->string('notes')->nullable();
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();

            $table->foreign('vehicle_id')->references('id')->on('vehicles');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tires');
    }
};
