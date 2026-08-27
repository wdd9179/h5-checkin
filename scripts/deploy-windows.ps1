# =====================================================================
#  晚间签到 - Windows Server 一键部署脚本
#  用法: 以管理员身份 PowerShell 跑这个脚本
#       powershell -ExecutionPolicy Bypass -File deploy-windows.ps1
#  可选参数:
#       -Repo "https://github.com/wdd9179/h5-checkin.git"
#       -ProjectPath "C:\web\chaqin"
#       -Domain "chaqin.example.com"   (不传则只用 HTTP/IP)
#       -Port 80
# =====================================================================

[CmdletBinding()]
param(
    [string]$Repo         = "https://github.com/wdd9179/h5-checkin.git",
    [string]$ProjectPath  = "C:\web\chaqin",
    [string]$Domain       = "",
    [int]$Port            = 80,
    [string]$AdminEmail   = "admin@chaqin.local",
    [string]$AdminPass    = "Admin@2026",
    [switch]$SkipReboot   # 装完不自动重启 IIS
)

# ============== 自助提权 (如果没管理员就重启为管理员) ==============
$isAdmin = ([Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
if (-not $isAdmin) {
    Write-Host "需要管理员权限, 正在以管理员身份重新启动..." -ForegroundColor Yellow
    $args2 = $PSBoundParameters
    Start-Process powershell.exe -ArgumentList @(
        "-NoProfile", "-ExecutionPolicy", "Bypass", "-File", "`"$PSCommandPath`"",
        $(if ($args2.Count) { $args2.GetEnumerator() | ForEach-Object { "-$($_.Key)", "`"$($_.Value)`"" } } else { @() })
    ) -Verb RunAs
    exit
}

# ============== 工具函数 ==============
$ErrorActionPreference = 'Stop'
$ProgressPreference    = 'SilentlyContinue'
$script:LogFile        = Join-Path $env:TEMP "chaqin-deploy-$(Get-Date -Format 'yyyyMMdd-HHmmss').log"

function Write-Log {
    param([string]$Message, [string]$Color = "White")
    $line = "[$(Get-Date -Format 'HH:mm:ss')] $Message"
    Write-Host $line -ForegroundColor $Color
    Add-Content -Path $script:LogFile -Value $line
}
function Write-Step   { param($n, $msg) Write-Host "`n[$n] " -NoNewline -ForegroundColor Cyan; Write-Host $msg -ForegroundColor White }
function Write-OK     { param($msg) Write-Host "  [OK] " -NoNewline -ForegroundColor Green; Write-Host $msg }
function Write-Warn   { param($msg) Write-Host "  [!!] " -NoNewline -ForegroundColor Yellow; Write-Host $msg }
function Write-Err    { param($msg) Write-Host "  [XX] " -NoNewline -ForegroundColor Red; Write-Host $msg }
function Test-Command { param($cmd) $null -ne (Get-Command $cmd -ErrorAction SilentlyContinue) }

# ============== 开始 ==============
Clear-Host
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "  晚间签到 H5 - Windows Server 一键部署"     -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""
Write-Log "日志文件: $script:LogFile" "Gray"

# ============== Step 1: 环境检查 ==============
Write-Step "1/9" "环境检查"
$os = Get-CimInstance Win32_OperatingSystem
Write-OK "系统: $($os.Caption) ($($os.Version))"
Write-OK "架构: $env:PROCESSOR_ARCHITECTURE"
$totalMem = [math]::Round($os.TotalVisibleMemorySize / 1MB, 1)
Write-OK "内存: ${totalMem}GB"
$freeDisk = [math]::Round((Get-PSDrive 'C').Free / 1GB, 1)
Write-OK "C 盘剩余: ${freeDisk}GB"

# ============== Step 2: 装依赖 (Git, PHP, Composer) ==============
Write-Step "2/9" "检查并安装依赖 (Git / PHP 8.3 / Composer)"

# --- Git ---
if (Test-Command git) {
    Write-OK "Git 已装: $((git --version) -join ' ')"
} else {
    Write-Host "  下载 Git..." -NoNewline
    $gitMsi = "$env:TEMP\git-installer.exe"
    Invoke-WebRequest -Uri "https://github.com/git-for-windows/git/releases/download/v2.47.0.windows.2/Git-2.47.0.2-64-bit.exe" -OutFile $gitMsi -UseBasicParsing
    Start-Process $gitMsi -ArgumentList "/VERYSILENT /NORESTART" -Wait
    Remove-Item $gitMsi -Force
    Write-Host " done" -ForegroundColor Green
    Write-OK "Git 已装"
}
[Environment]::SetEnvironmentVariable("Path", $env:Path + ";C:\Program Files\Git\cmd", "Machine") | Out-Null
$env:Path = $env:Path + ";C:\Program Files\Git\cmd"

# --- PHP 8.3 NTS ---
if (Test-Path "C:\php\php.exe") {
    $phpVer = & C:\php\php.exe -r "echo PHP_VERSION;"
    Write-OK "PHP 已装: $phpVer"
} else {
    Write-Host "  下载 PHP 8.3 NTS (约 30MB)..." -ForegroundColor Yellow
    $phpZip = "$env:TEMP\php.zip"
    Invoke-WebRequest -Uri "https://windows.php.net/downloads/releases/archives/php-8.3.9-nts-Win32-vs16-x64.zip" -OutFile $phpZip -UseBasicParsing
    Expand-Archive $phpZip -DestinationPath "$env:TEMP\php-extract" -Force
    $extracted = Get-ChildItem "$env:TEMP\php-extract" -Directory | Select-Object -First 1
    Move-Item $extracted.FullName "C:\php" -Force
    Remove-Item $phpZip, "$env:TEMP\php-extract" -Recurse -Force
    Copy-Item C:\php\php.ini-development C:\php\php.ini

    # 启用常用扩展
    $ini = Get-Content C:\php\php.ini
    foreach ($ext in 'fileinfo','mbstring','curl','openssl','pdo_sqlite','sqlite3','zip','gd','intl','bcmath','tokenizer','xml','ctype','json') {
        $ini = $ini -replace "^;extension=$ext$", "extension=$ext"
    }
    $ini = $ini -replace '^memory_limit = .*', 'memory_limit = 512M'
    $ini = $ini -replace '^post_max_size = .*', 'post_max_size = 20M'
    $ini = $ini -replace '^upload_max_filesize = .*', 'upload_max_filesize = 20M'
    $ini = $ini -replace '^;cgi.fix_pathinfo=1', 'cgi.fix_pathinfo = 0'
    $ini = $ini -replace '^;date.timezone =.*', 'date.timezone = Asia/Shanghai'
    $ini | Set-Content C:\php\php.ini -Encoding UTF8
    Write-OK "PHP 8.3 已装到 C:\php"
}

[Environment]::SetEnvironmentVariable("Path", $env:Path + ";C:\php", "Machine") | Out-Null
$env:Path = $env:Path + ";C:\php"

# --- Composer ---
if (Test-Command composer) {
    Write-OK "Composer 已装: $((composer --version) 2>$null)"
} else {
    Write-Host "  下载 Composer..." -ForegroundColor Yellow
    Invoke-WebRequest -Uri "https://getcomposer.org/installer" -OutFile "$env:TEMP\composer-setup.php" -UseBasicParsing
    & C:\php\php.exe "$env:TEMP\composer-setup.php" --install-dir=C:\php --filename=composer
    Remove-Item "$env:TEMP\composer-setup.php" -Force
    Write-OK "Composer 已装"
}

# ============== Step 3: 装 IIS + URL Rewrite ==============
Write-Step "3/9" "检查并启用 IIS + URL Rewrite + CGI"

$iisFeature = Get-WindowsFeature -Name Web-Server -ErrorAction SilentlyContinue
if ($iisFeature -and $iisFeature.InstallState -eq "Installed") {
    Write-OK "IIS 已装"
} else {
    Write-Host "  装 IIS (要 1-2 分钟)..." -ForegroundColor Yellow
    Install-WindowsFeature -Name Web-Server,Web-CGI,Web-Common-Http,Web-Default-Doc,Web-Static-Content,Web-Http-Errors,Web-Http-Redirect,Web-Security,Web-Filtering,Web-Request-Monitor | Out-Null
    Write-OK "IIS 已装"
}

# CGI 必须
$cgi = Get-WindowsFeature -Name Web-CGI -ErrorAction SilentlyContinue
if ($cgi.InstallState -ne "Installed") {
    Install-WindowsFeature -Name Web-CGI | Out-Null
    Write-OK "CGI 启用"
}

# URL Rewrite
$rewriteInstalled = Get-WebGlobalModule -Name "Rewrite" -ErrorAction SilentlyContinue
if ($rewriteInstalled) {
    Write-OK "URL Rewrite 已装"
} else {
    Write-Host "  下载 URL Rewrite (约 5MB)..." -ForegroundColor Yellow
    $rwMsi = "$env:TEMP\rewrite_amd64.msi"
    try {
        Invoke-WebRequest -Uri "https://download.microsoft.com/download/1/2/8/128E2E22-C1D9-46F1-9DB1-25B4F62A4F1B/rewrite_amd64.msi" -OutFile $rwMsi -UseBasicParsing -TimeoutSec 30
        Start-Process msiexec.exe -ArgumentList "/i `"$rwMsi`" /qn" -Wait
        Remove-Item $rwMsi -Force
        Write-OK "URL Rewrite 已装"
    } catch {
        Write-Warn "URL Rewrite 下载失败, 请手动装: https://www.iis.net/downloads/microsoft/url-rewrite"
    }
}

# ============== Step 4: 拉代码 ==============
Write-Step "4/9" "拉项目代码到 $ProjectPath"

if (Test-Path "$ProjectPath\artisan") {
    Write-OK "项目已存在, 拉最新代码"
    Set-Location $ProjectPath
    git pull 2>&1 | Out-Null
} else {
    if (-not (Test-Path (Split-Path $ProjectPath))) {
        New-Item -ItemType Directory -Path (Split-Path $ProjectPath) -Force | Out-Null
    }
    git clone $Repo $ProjectPath 2>&1 | Out-Null
    Set-Location $ProjectPath
    Write-OK "项目已 clone"
}

# ============== Step 5: composer install ==============
Write-Step "5/9" "装 PHP 依赖 (composer install)"

# 用 .phar 走 php 直接装, 跳过 composer.bat
$composerPhar = (Get-Command composer).Source
& C:\php\php.exe $composerPhar install --no-dev --optimize-autoloader --no-interaction 2>&1 | Select-Object -Last 8
Write-OK "composer install 完成"

# ============== Step 6: 配 .env ==============
Write-Step "6/9" "配置 .env"

if (-not (Test-Path ".env")) {
    Copy-Item .env.example .env
    Write-OK "复制 .env.example -> .env"
}
& C:\php\php.exe artisan key:generate --force 2>&1 | Out-Null

# 改 .env 关键配置
$envContent = Get-Content .env -Raw
$appUrl = if ($Domain) { "http://$Domain" } else { "http://localhost" }
$envContent = $envContent -replace '(?m)^APP_NAME=.*$',       'APP_NAME=晚间签到'
$envContent = $envContent -replace '(?m)^APP_ENV=.*$',         'APP_ENV=production'
$envContent = $envContent -replace '(?m)^APP_DEBUG=.*$',       'APP_DEBUG=false'
$envContent = $envContent -replace '(?m)^APP_URL=.*$',         "APP_URL=$appUrl"
$envContent = $envContent -replace '(?m)^DB_CONNECTION=.*$',   'DB_CONNECTION=sqlite'
$envContent = $envContent -replace '(?m)^ADMIN_NAME=.*$',      "ADMIN_NAME=管理员"
$envContent = $envContent -replace '(?m)^ADMIN_EMAIL=.*$',     "ADMIN_EMAIL=$AdminEmail"
$envContent = $envContent -replace '(?m)^ADMIN_PASSWORD=.*$',  "ADMIN_PASSWORD=$AdminPass"
$envContent = $envContent -replace '(?m)^LOG_LEVEL=.*$',       'LOG_LEVEL=info'
Set-Content .env -Value $envContent -Encoding UTF8 -NoNewline
Write-OK ".env 已配"
Write-OK "  APP_URL = $appUrl"
Write-OK "  ADMIN_EMAIL = $AdminEmail"
Write-OK "  ADMIN_PASSWORD = $AdminPass (部署后请改!)"

# ============== Step 7: 初始化 ==============
Write-Step "7/9" "初始化数据库 + 缓存"

if (-not (Test-Path "database\database.sqlite")) {
    New-Item database\database.sqlite -ItemType File -Force | Out-Null
    Write-OK "创建 database\database.sqlite"
}

& C:\php\php.exe artisan migrate --force 2>&1 | Select-Object -Last 5
& C:\php\php.exe artisan db:seed --force 2>&1 | Select-Object -Last 3
& C:\php\php.exe artisan storage:link 2>&1 | Out-Null
& C:\php\php.exe artisan config:cache 2>&1 | Out-Null
& C:\php\php.exe artisan route:cache 2>&1 | Out-Null
& C:\php\php.exe artisan view:cache 2>&1 | Out-Null
Write-OK "初始化完成"

# ============== Step 8: 文件权限 ==============
Write-Step "8/9" "设置文件权限 (IIS_IUSRS)"

$dirs = @("$ProjectPath\storage", "$ProjectPath\bootstrap\cache", "$ProjectPath\database")
foreach ($d in $dirs) {
    icacls $d /grant "IIS_IUSRS:(OI)(CI)F" /T /C /Q 2>&1 | Out-Null
    Write-OK "授权: $d"
}

# ============== Step 9: 配 IIS 站点 ==============
Write-Step "9/9" "配置 IIS 站点"

Import-Module WebAdministration -ErrorAction SilentlyContinue

$siteName = "ChaQin"
$physicalPath = "$ProjectPath\public"
$site = Get-Website -Name $siteName -ErrorAction SilentlyContinue
if ($site) {
    Write-OK "站点 $siteName 已存在, 删了重建"
    Remove-Website -Name $siteName
}

# 删默认站点
$def = Get-Website -Name "Default Web Site" -ErrorAction SilentlyContinue
if ($def) { Remove-Website -Name "Default Web Site" | Out-Null }

New-Website -Name $siteName -PhysicalPath $physicalPath -Port $Port -ApplicationPool "DefaultAppPool" -Force | Out-Null
Write-OK "创建 IIS 站点: $siteName -> $physicalPath (端口 $Port)"

# 确保 AppPool 是 No Managed Code
Set-ItemProperty "IIS:\AppPools\DefaultAppPool" managedRuntimeVersion "" -ErrorAction SilentlyContinue
Set-ItemProperty "IIS:\AppPools\DefaultAppPool" startMode "AlwaysRunning" -ErrorAction SilentlyContinue

# 注册 PHP FastCGI
$existingHandler = Get-WebHandler -Name "PHP_via_FastCGI" -ErrorAction SilentlyContinue
if (-not $existingHandler) {
    Add-WebHandler -Name "PHP_via_FastCGI" -Path "*.php" -Verb "*" -Modules "FastCgiModule" -ScriptProcessor "C:\php\php-cgi.exe" -ResourceType File -ErrorAction SilentlyContinue
    Write-OK "注册 PHP FastCGI 处理"
}

# 写 web.config (Laravel URL 重写)
$webConfig = @'
<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <defaultDocument>
            <files><clear/><add value="index.php"/></files>
        </defaultDocument>
        <security>
            <requestFiltering>
                <hiddenSegments><add segment="vendor"/><add segment="storage"/></hiddenSegments>
            </requestFiltering>
        </security>
        <rewrite>
            <rules>
                <rule name="Laravel" stopProcessing="true">
                    <match url="^(.*)$" ignoreCase="false"/>
                    <conditions logicalGrouping="MatchAll">
                        <add input="{REQUEST_FILENAME}" matchType="IsFile" negate="true"/>
                        <add input="{REQUEST_FILENAME}" matchType="IsDirectory" negate="true"/>
                    </conditions>
                    <action type="Rewrite" url="index.php" appendQueryString="true"/>
                </rule>
            </rules>
        </rewrite>
    </system.webServer>
</configuration>
'@
Set-Content "$physicalPath\web.config" -Value $webConfig -Encoding UTF8
Write-OK "写入 web.config"

# 重启 IIS
iisreset | Out-Null
Write-OK "IIS 重启"

# 防火墙 (Windows Firewall)
$fw = Get-NetFirewallProfile -ErrorAction SilentlyContinue
if ($fw) {
    $rule = Get-NetFirewallRule -DisplayName "ChaQin HTTP" -ErrorAction SilentlyContinue
    if (-not $rule) {
        New-NetFirewallRule -DisplayName "ChaQin HTTP" -Direction Inbound -Protocol TCP -LocalPort $Port -Action Allow | Out-Null
        Write-OK "防火墙开放 $Port 端口"
    } else {
        Write-OK "防火墙规则已存在"
    }
}

# ============== 完成 ==============
Write-Host ""
Write-Host "=============================================" -ForegroundColor Green
Write-Host "  部署完成!" -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Green
Write-Host ""
Write-Host "访问地址:" -ForegroundColor Cyan
if ($Domain) {
    Write-Host "  http://$Domain/                (学生端)" -ForegroundColor White
    Write-Host "  http://$Domain/admin/login     (后台)" -ForegroundColor White
} else {
    Write-Host "  http://<服务器IP>/              (学生端)" -ForegroundColor White
    Write-Host "  http://<服务器IP>/admin/login   (后台)" -ForegroundColor White
}
Write-Host ""
Write-Host "管理员账号:" -ForegroundColor Cyan
Write-Host "  邮箱: $AdminEmail" -ForegroundColor White
Write-Host "  密码: $AdminPass" -ForegroundColor White
Write-Host ""
Write-Host "后续操作:" -ForegroundColor Yellow
Write-Host "  1. 阿里云控制台 -> ECS 安全组 -> 入方向放行 80/443 端口" -ForegroundColor White
Write-Host "  2. 浏览器访问学生端, 测试打开 -> 选名字 -> 签到流程" -ForegroundColor White
Write-Host "  3. 登录后台, 改默认管理员密码 (用 php artisan tinker 改)" -ForegroundColor White
Write-Host "  4. 后台 -> 学生管理 -> 下载 Excel 模板 -> 导入学生名单" -ForegroundColor White
Write-Host "  5. (可选) 申请阿里云免费 SSL 证书, 配 HTTPS" -ForegroundColor White
Write-Host ""
Write-Host "日志文件: $script:LogFile" -ForegroundColor Gray
Write-Host ""

# 输出总结
Write-Host "如果遇到问题, 跑下面命令看具体错误:" -ForegroundColor Yellow
Write-Host "  cd $ProjectPath" -ForegroundColor White
Write-Host "  C:\php\php.exe artisan optimize:clear" -ForegroundColor White
Write-Host "  Get-Content storage\logs\laravel.log -Tail 50" -ForegroundColor White
Write-Host ""
