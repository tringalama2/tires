<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('placements', function (Blueprint $table) {
            // Swap rotations: retiring tire has to_position = null (leaves vehicle);
            // optional retiring tread means tread_center may also be null.
            $table->string('to_position', 2)->nullable()->change();
            $table->decimal('tread_center', 4, 1)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('placements', function (Blueprint $table) {
            $table->string('to_position', 2)->nullable(false)->change();
            $table->decimal('tread_center', 4, 1)->nullable(false)->change();
        });
    }
};
