<?php
// scripts/verify_pages.php - 更精准的端到端验证
$base = 'http://127.0.0.1:8000';
$jar = sys_get_temp_dir() . '/verify_cookies.txt';

function req(string $method, string $url, ?string $body = null, array $headers = []): array {
    global $jar;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => false, CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar,
        CURLOPT_TIMEOUT => 15,
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
    return preg_match('/name="_token"[^>]*?value="([^"]+)"/', $body, $m) ? $m[1] : null;
}

$pass = 0; $fail = 0;
function check(string $name, bool $ok, string $detail = ''): void {
    global $pass, $fail;
    if ($ok) { $pass++; echo "  ✓ $name"; }
    else { $fail++; echo "  ✗ $name"; }
    if ($detail) echo " ($detail)";
    echo "\n";
}

echo "=== 后台精确验证 ===\n";

// 登录
$r = req('GET', $base . '/admin/login');
$token = csrf($r['body']);
$r = req('POST', $base . '/admin/login', http_build_query(['_token'=>$token,'email'=>'admin@chaqin.local','password'=>'admin123456']), ['Content-Type: application/x-www-form-urlencoded']);
check('管理员登录成功', $r['code'] === 302 && str_contains($r['location'], '/admin'));

// 1. Dashboard
$r = req('GET', $base . '/admin');
$body = $r['body'];
preg_match_all('/text-2xl font-bold[^>]*>(\d+)</', $body, $m);
$stats = $m[1] ?? [];
check('Dashboard 应签到 5', ($stats[0] ?? '') === '5');
check('Dashboard 已签到 1', ($stats[1] ?? '') === '1');
check('Dashboard 迟到 0', ($stats[2] ?? '') === '0');
check('Dashboard 未签到 4', ($stats[3] ?? '') === '4');
check('Dashboard 标题', str_contains($body, '今日晚间签到'));

// 2. Students
$r = req('GET', $base . '/admin/students');
$body = $r['body'];
$names = ['王小明','李小红','张三','李四','王五'];
$allFound = true;
foreach ($names as $n) {
    if (!str_contains($body, $n)) { $allFound = false; break; }
}
check('学生列表 5 人全在', $allFound);

// 3. Checkins detail
$r = req('GET', $base . '/admin/checkins');
$body = $r['body'];
check('签到详情有"应签"', str_contains($body, '应签'));
check('签到详情有"未签到"', str_contains($body, '未签到'));
check('签到详情有"迟到"', str_contains($body, '迟到'));

// 4. Absent
$r = req('GET', $base . '/admin/checkins/absent');
$body = $r['body'];
preg_match('/共 <span[^>]*>(\d+)<\/span> 人未签到/', $body, $m);
$absentCount = $m[1] ?? '?';
check('未签到页面显示"共 4 人"', $absentCount === '4', "actual: $absentCount");
$allAbsent = true;
foreach (['李小红','张三','李四','王五'] as $n) {
    if (!str_contains($body, $n)) { $allAbsent = false; break; }
}
check('未签到名单有 4 人姓名', $allAbsent);

// 5. History
$r = req('GET', $base . '/admin/checkins/history');
$body = $r['body'];
$historyOk = true;
foreach ($names as $n) {
    if (!str_contains($body, $n)) { $historyOk = false; break; }
}
check('历史总览 5 人都在', $historyOk);
check('历史总览有"签到率"', str_contains($body, '签到率'));

// 6. Share link
$r = req('GET', $base . '/admin/rules/share');
$body = $r['body'];
preg_match('/id="linkBox"[^>]*>([^<]+)</', $body, $m);
$url = $m[1] ?? '';
check('Share 页生成链接', str_starts_with($url, 'http://127.0.0.1:8000'), "url: $url");
check('Share 页含"晚间签到"消息模板', str_contains($body, '🌙 今日晚间签到'));

// 7. Excel template
$r = req('GET', $base . '/admin/students/template');
check('Excel 模板 200', $r['code'] === 200);
check('Excel 模板是 xlsx', str_contains($r['headers'], 'spreadsheetml'));

// 8. Rules
$r = req('GET', $base . '/admin/rules');
$body = $r['body'];
check('规则列表有今日', str_contains($body, date('Y-m-d')));

echo "\n";
echo "通过 $pass / 失败 $fail\n";
exit($fail === 0 ? 0 : 1);
