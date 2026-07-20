<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_terms_acceptances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('context', ['colaborador', 'supervisor']);
            $table->unsignedInteger('warning_version');
            $table->text('warning_text_snapshot');
            $table->timestamp('accepted_at');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'context', 'warning_version'], 'terms_acceptances_user_context_version_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_terms_acceptances');
    }
};
