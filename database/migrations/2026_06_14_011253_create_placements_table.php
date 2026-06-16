<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('placements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('rotation_id');
            $table->uuid('tire_id');
            $table->string('from_position', 2)->nullable()->comment('null only on is_setup rotations');
            $table->string('to_position', 2);
            $table->decimal('tread_center', 4, 1)->comment('32nds of an inch; .5 allowed');
            $table->decimal('tread_inner', 4, 1)->nullable();
            $table->decimal('tread_outer', 4, 1)->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_feathering')->default(false);
            $table->boolean('is_cupped')->default(false);
            $table->timestamps();

            $table->foreign('rotation_id')->references('id')->on('rotations')->cascadeOnDelete();
            $table->foreign('tire_id')->references('id')->on('tires');

            $table->unique(['rotation_id', 'tire_id']);
            $table->unique(['rotation_id', 'to_position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placements');
    }
};
