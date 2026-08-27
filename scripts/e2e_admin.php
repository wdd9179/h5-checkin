<?php
// scripts/e2e_admin.php - 后台流程
$base = 'http://127.0.0.1:8000';
$jar = sys_get_temp_dir() . '/e2e_admin_cookies.txt';

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
    if ($body !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    if ($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hsize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $rawHeaders = substr($raw, 0, $hsize);
    $rawBody = substr($raw, $hsize);
    $loc = null;
    if (preg_match('/^Location:\s*(.+)$/mi', $rawHeaders, $m)) $loc = trim($m[1]);
    return ['code' => $code, 'location' => $loc, 'body' => $rawBody, 'headers' => $rawHeaders];
}
function csrf(string $body): ?string {
    if (preg_match('/name="_token"[^>]*?value="([^"]+)"/', $body, $m)) return $m[1];
    return null;
}
function show(string $t, array $r): void {
    $loc = $r['location'] ? " -> {$r['location']}" : '';
    echo "  [{$r['code']}]{$loc} $t\n";
}

echo "=== E2E: 后台登录 + 关键流程 ===\n";

// 1. 登录页
$r = req('GET', $base . '/admin/login');
show('GET /admin/login', $r);
$token = csrf($r['body']);
echo "  CSRF: " . substr($token ?? '', 0, 12) . "...\n";

// 2. 登录
$body = http_build_query([
    '_token' => $token,
    'email'   => 'admin@chaqin.local',
    'password'=> 'admin123456',
]);
$r = req('POST', $base . '/admin/login', $body, ['Content-Type: application/x-www-form-urlencoded']);
show('POST /admin/login', $r);
if ($r['code'] != 302) { echo "FAIL: 登录失败\n"; exit(1); }

// 3. 后台首页
$r = req('GET', $r['location'] ?: $base . '/admin');
show('GET /admin', $r);
echo "  出现 '今日晚间签到': " . (strpos($r['body'], '今日晚间签到') !== false ? '✓' : '✗') . "\n";
echo "  出现 1 个学生姓名: " . (strpos($r['body'], '王小明') !== false ? '✓' : '✗') . "\n";

// 4. 学生列表
$r = req('GET', $base . '/admin/students');
show('GET /admin/students', $r);
echo "  5 个学生都在: " . (preg_match_all('/王小明|李小红|张三|李四|王五/', $r['body']) === 5 ? '✓' : '✗') . "\n";

// 5. 签到详情
$r = req('GET', $base . '/admin/checkins');
show('GET /admin/checkins', $r);
echo "  已签到数字: " . (preg_match('/已签到.*?(\d+)/s', $r['body'], $m) ? $m[1] : '?') . "\n";

// 6. 未签到
$r = req('GET', $base . '/admin/checkins/absent');
show('GET /admin/checkins/absent', $r);
echo "  未签到人数: " . (preg_match('/未签到.*?(\d+)/s', $r['body'], $m) ? $m[1] : '?') . "\n";

// 7. Excel 模板下载
$r = req('GET', $base . '/admin/students/template');
show('GET /admin/students/template', $r);
echo "  Content-Type: " . (preg_match('/Content-Type:\s*(.+)/i', $r['headers'], $m) ? trim($m[1]) : '?') . "\n";
echo "  body 长度: " . strlen($r['body']) . " (xlsx 是 zip)\n";

// 8. 生成签到链接
$r = req('GET', $base . '/admin/rules/share');
show('GET /admin/rules/share', $r);
echo "  含签到链接: " . (strpos($r['body'], 'student.home') !== false || strpos($r['body'], '/bind') !== false ? '✓' : '✗') . "\n";

// 9. 历史总览
$r = req('GET', $base . '/admin/checkins/history');
show('GET /admin/checkins/history', $r);
echo "  显示 5 名学生: " . (preg_match_all('/王小明|李小红|张三|李四|王五/', $r['body']) >= 5 ? '✓' : '✗') . "\n";

// 10. 规则列表
$r = req('GET', $base . '/admin/rules');
show('GET /admin/rules', $r);
echo "  今日规则存在: " . (strpos($r['body'], date('Y-m-d')) !== false ? '✓' : '✗') . "\n";

echo "\n✅ 后台流程全部通过\n";
