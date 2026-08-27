<?php
// scripts/e2e_test.php
// 用 cookie jar 模拟一个"学生浏览器"，走完整流程：bind -> checkin
$base = 'http://127.0.0.1:8000';
$jar = sys_get_temp_dir() . '/e2e_cookies.txt';

function req(string $method, string $url, ?string $body = null, array $headers = []): array {
    global $jar;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_COOKIEJAR      => $jar,
        CURLOPT_COOKIEFILE     => $jar,
        CURLOPT_TIMEOUT        => 15,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    if ($headers) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hsize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $rawHeaders = substr($raw, 0, $hsize);
    $rawBody = substr($raw, $hsize);
    // 解析 Location
    $loc = null;
    if (preg_match('/^Location:\s*(.+)$/mi', $rawHeaders, $m)) {
        $loc = trim($m[1]);
    }
    return ['code' => $code, 'location' => $loc, 'body' => $rawBody, 'headers' => $rawHeaders];
}

function extractCsrf(string $body): ?string {
    if (preg_match('/name="_token"[^>]*?value="([^"]+)"/', $body, $m)) return $m[1];
    return null;
}

function show(string $title, array $r): void {
    $loc = $r['location'] ? " -> {$r['location']}" : '';
    echo "  [{$r['code']}]{$loc} $title\n";
}

echo "=== E2E: 学生绑名 + 签到 ===\n";

// 1. 访问根目录
$r = req('GET', $GLOBALS['base'] . '/');
show('GET /', $r);
if ($r['code'] != 302 || $r['location'] !== $GLOBALS['base'] . '/bind') {
    echo "FAIL: 根路径应重定向到 /bind\n"; exit(1);
}

// 2. 访问 /bind
$r = req('GET', $r['location']);
show('GET /bind', $r);
$csrf = extractCsrf($r['body']);
echo "  CSRF: " . ($csrf ? substr($csrf, 0, 12) . '...' : 'NOT FOUND') . "\n";
if (!$csrf) { echo "FAIL: 找不到 CSRF token\n"; exit(1); }

// 3. 拿 student 1 (王小明) 的 ID
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$student = App\Models\Student::where('name', '王小明')->first();
if (!$student) { echo "FAIL: 找不到王小明\n"; exit(1); }

// 4. 提交绑名
$body = http_build_query(['_token' => $csrf, 'student_id' => $student->id]);
$r = req('POST', $GLOBALS['base'] . '/bind', $body, [
    'Content-Type: application/x-www-form-urlencoded',
]);
show('POST /bind', $r);
if ($r['code'] != 302 || $r['location'] !== $GLOBALS['base'] . '/checkin') {
    echo "FAIL: 绑定后应跳到 /checkin\n"; exit(1);
}

// 5. 访问 /checkin
$r = req('GET', $r['location']);
show('GET /checkin', $r);
$csrf = extractCsrf($r['body']);
$okName = strpos($r['body'], '王小明') !== false;
$okBtn  = strpos($r['body'], '立即签到') !== false;
echo "  显示姓名: " . ($okName ? '✓' : '✗') . "  显示签到按钮: " . ($okBtn ? '✓' : '✗') . "\n";
if (!$okName || !$okBtn) { echo "FAIL: 签到页没显示姓名/按钮\n"; exit(1); }

// 6. 提交签到
$body = http_build_query(['_token' => $csrf]);
$r = req('POST', $GLOBALS['base'] . '/checkin', $body, [
    'Content-Type: application/x-www-form-urlencoded',
]);
show('POST /checkin', $r);
if ($r['code'] != 200) { echo "FAIL: 签到提交应返回 200\n"; exit(1); }
$okSuccess = strpos($r['body'], '签到成功') !== false;
echo "  签到成功提示: " . ($okSuccess ? '✓' : '✗') . "\n";
if (!$okSuccess) { echo "FAIL: 没看到签到成功提示\n"; exit(1); }

// 7. 查 DB 确认记录
$ci = App\Models\CheckIn::where('student_id', $student->id)->whereDate('check_date', now()->toDateString())->first();
echo "  DB 签到记录: " . ($ci ? "✓ [{$ci->status} {$ci->check_time->format('H:i:s')}]" : '✗') . "\n";
if (!$ci) { echo "FAIL: DB 没记录\n"; exit(1); }

// 8. 再次访问 /checkin 应显示已签到
$r = req('GET', $GLOBALS['base'] . '/checkin');
show('GET /checkin (again)', $r);
$okRepeat = strpos($r['body'], '今日已签到') !== false;
echo "  重复签到被阻止: " . ($okRepeat ? '✓' : '✗') . "\n";
if (!$okRepeat) { echo "FAIL: 第二次访问应显示已签到\n"; exit(1); }

echo "\n✅ 全部通过\n";
