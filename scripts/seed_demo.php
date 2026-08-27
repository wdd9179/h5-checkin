<?php
// scripts/seed_demo.php - 一次性脚本：插入演示数据
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Student;
use App\Models\CheckInRule;
use Illuminate\Support\Facades\DB;

DB::transaction(function () {
    // 学生
    $names = [
        ['name' => '王小明', 'student_no' => '001', 'class_name' => '1班', 'dormitory' => '101', 'phone' => '13800000001'],
        ['name' => '李小红', 'student_no' => '002', 'class_name' => '1班', 'dormitory' => '102', 'phone' => '13800000002'],
        ['name' => '张三',   'student_no' => '003', 'class_name' => '1班', 'dormitory' => '103', 'phone' => '13800000003'],
        ['name' => '李四',   'student_no' => '004', 'class_name' => '2班', 'dormitory' => '201', 'phone' => '13800000004'],
        ['name' => '王五',   'student_no' => '005', 'class_name' => '2班', 'dormitory' => '202', 'phone' => '13800000005'],
    ];
    foreach ($names as $n) {
        Student::updateOrCreate(
            ['name' => $n['name'], 'student_no' => $n['student_no']],
            $n + ['status' => 'active']
        );
    }

    // 今日签到规则：21:30 ~ 22:00 正常，~ 22:30 迟到
    CheckInRule::updateOrCreate(
        ['check_date' => now()->toDateString()],
        [
            'start_time'      => '21:30:00',
            'normal_end_time' => '22:00:00',
            'final_end_time'  => '22:30:00',
            'status'          => 'open',
            'note'            => '演示数据',
        ]
    );
});

echo 'students: ' . Student::count() . PHP_EOL;
echo 'rules: ' . CheckInRule::count() . PHP_EOL;
echo 'today rule: ' . (CheckInRule::forDate(now()->toDateString())?->check_date?->toDateString() ?? 'none') . PHP_EOL;
