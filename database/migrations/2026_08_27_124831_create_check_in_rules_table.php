<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_in_rules', function (Blueprint $table) {
            $table->id();
            $table->date('check_date')->comment('签到日期');
            $table->time('start_time')->comment('开始时间 (例如 21:30)');
            $table->time('normal_end_time')->comment('正常签到截止 (例如 22:00)');
            $table->time('final_end_time')->comment('最终签到截止 (例如 22:30)');
            // status: open=开启, closed=关闭
            $table->string('status', 20)->default('open')->index();
            $table->string('note', 255)->nullable();
            $table->timestamps();

            // 一天只能有一条规则
            $table->unique('check_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('check_in_rules');
    }
};
