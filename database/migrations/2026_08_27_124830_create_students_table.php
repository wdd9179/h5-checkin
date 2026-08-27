<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->comment('姓名');
            $table->string('student_no', 50)->nullable()->comment('学号');
            $table->string('class_name', 50)->nullable()->comment('班级');
            $table->string('dormitory', 50)->nullable()->comment('宿舍');
            $table->string('phone', 30)->nullable()->comment('手机号');
            // 微信 OpenID；第一版用 cookie UUID 模拟，结构保留供第二版切真
            $table->string('openid', 128)->nullable()->unique()->comment('微信 OpenID (或模拟标识)');
            // status: active=正常 disabled=已禁用
            $table->string('status', 20)->default('active')->index();
            $table->timestamp('bound_at')->nullable()->comment('微信绑定时间');
            $table->timestamps();

            $table->index(['class_name', 'dormitory']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
