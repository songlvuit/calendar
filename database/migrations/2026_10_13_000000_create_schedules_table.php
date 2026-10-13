<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->dateTime('scheduled_at');              // Thời gian có mặt
            $table->string('lecturer', 150);                // Giảng viên
            $table->string('subject', 200);                 // Môn
            $table->string('lecturer_phone', 20)->nullable(); // SĐT giảng viên
            $table->string('on_duty', 150)->nullable();     // Người trực
            $table->timestamps();
            $table->softDeletes();                          // Lịch đã huỷ

            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
