<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_ins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('check_date')->comment('签到日期');
            $table->timestamp('check_time')->comment('签到时间');
            // status: normal=正常, late=迟到
            $table->string('status', 20)->index();
            $table->string('ip', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();

            // 防止重复签到
            $table->unique(['student_id', 'check_date'], 'uniq_student_date');
            $table->index('check_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_ins');
    }
};
