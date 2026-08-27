<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// 清除所有 openid (重置绑定) + 签到记录
App\Models\CheckIn::truncate();
App\Models\Student::query()->update(['openid' => null, 'bound_at' => null]);
echo "重置完成\n";
echo "students: " . App\Models\Student::count() . "\n";
echo "openids: " . App\Models\Student::whereNotNull('openid')->count() . "\n";
echo "check_ins: " . App\Models\CheckIn::count() . "\n";
