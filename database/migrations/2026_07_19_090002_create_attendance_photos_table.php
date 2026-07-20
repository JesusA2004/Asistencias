<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attendance_event_id')->nullable()->constrained('attendance_events')->nullOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('captured_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_point_id')->constrained()->cascadeOnDelete();
            $table->string('photo_path', 255);
            $table->string('thumbnail_path', 255);
            $table->enum('capture_type', ['entrada', 'salida', 'incidencia', 'manual']);
            $table->enum('capture_origin', ['colaborador', 'supervisor', 'admin', 'rh']);
            $table->timestamp('captured_at');
            $table->timestamp('server_time');
            $table->timestamp('device_time')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['attendance_id']);
            $table->index(['employee_id', 'captured_at']);
            $table->index(['client_id', 'service_point_id', 'captured_at']);
            $table->index(['capture_origin', 'capture_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_photos');
    }
};
