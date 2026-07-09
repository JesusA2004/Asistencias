<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->time('start_time');
            $table->time('end_time');
            $table->json('work_days')->nullable();
            $table->unsignedSmallInteger('tolerance_minutes')->default(0);
            $table->enum('status', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
