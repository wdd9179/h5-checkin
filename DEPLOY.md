# 部署文档

本文档说明如何把 **晚间签到** 系统部署到一台 Linux 公网服务器（Ubuntu 22.04 / Debian 12 为例）。

---

## 0. 服务器最低要求

| 项目 | 要求 |
| --- | --- |
| 系统 | Ubuntu 22.04 / Debian 12 (其他发行版也行) |
| CPU / 内存 | 1 vCPU / 512 MB 足够 30 人小班 |
| 磁盘 | 10 GB |
| PHP | **8.2+**（推荐 8.3） |
| Web | Nginx 1.20+ |
| 数据库 | MySQL 8.0+ / MariaDB 10.6+ |
| HTTPS | 必需（微信内置浏览器要求 HTTPS） |

> 单班 30 人规模，用最小的 1C1G 云服务器足够，CPU 长期空闲。

---

## 1. 系统级依赖

```bash
# 更新源
sudo apt update && sudo apt upgrade -y

# 装 PHP 8.3 + 扩展（Ubuntu 22.04 默认源是 8.1，需要 ondrej/php PPA）
sudo apt install -y software-properties-common ca-certificates lsb-release apt-transport-https
sudo LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php -y
sudo apt update

sudo apt install -y \
  php8.3 php8.3-cli php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-zip php8.3-bcmath php8.3-intl php8.3-gd \
  php8.3-sqlite3 php8.3-tokenizer php8.3-fileinfo php8.3-opcache

# Nginx + MySQL
sudo apt install -y nginx mysql-server

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

验证：

```bash
php -v           # 应是 8.3.x
composer -V
nginx -v
mysql --version
```

---

## 2. 准备项目

```bash
# 上传代码（从本机 scp 或 git clone）
sudo mkdir -p /var/www/chaqin
sudo chown -R $USER:www-data /var/www/chaqin
cd /var/www/chaqin
# git clone <your-repo-url> .

# 装 PHP 依赖
composer install --no-dev --optimize-autoloader

# 前端资源（如在本地已 build，可跳过；服务器 build 需 node）
npm ci
npm run build
```

---

## 3. 数据库

```bash
sudo mysql <<SQL
CREATE DATABASE chaqin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'chaqin'@'localhost' IDENTIFIED BY 'CHANGE_ME_强密码';
GRANT ALL ON chaqin.* TO 'chaqin'@'localhost';
FLUSH PRIVILEGES;
SQL
```

---

## 4. 环境配置

```bash
cp .env.example .env
php artisan key:generate
chmod 600 .env
sudo chown www-data:www-data .env
```

编辑 `.env`，重点改这些项：

```dotenv
APP_NAME=晚间签到
APP_ENV=production
APP_DEBUG=false
APP_URL=https://chaqin.your-domain.com

# 数据库
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chaqin
DB_USERNAME=chaqin
DB_PASSWORD=刚才设置的密码

# Session / Cache / Queue 都走 database 即可
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

# 管理员初始账号
ADMIN_NAME=班主任
ADMIN_EMAIL=teacher@your-school.com
ADMIN_PASSWORD=首次登录后立刻改掉

LOG_CHANNEL=stack
LOG_LEVEL=info
```

---

## 5. 初始化数据库

```bash
php artisan migrate --force
php artisan db:seed --force       # 会按 .env 的 ADMIN_* 自动创建管理员
php artisan storage:link
```

---

## 6. 权限

```bash
sudo chown -R www-data:www-data /var/www/chaqin
sudo find /var/www/chaqin -type d -exec chmod 755 {} \;
sudo find /var/www/chaqin -type f -exec chmod 644 {} \;
# storage 与 bootstrap/cache 需可写
sudo chmod -R ug+rwx /var/www/chaqin/storage /var/www/chaqin/bootstrap/cache
```

---

## 7. PHP-FPM + Nginx 站点

### 7.1 PHP-FPM 池（默认即可，确认监听方式）

`/etc/php/8.3/fpm/pool.d/www.conf`：

```ini
listen = /run/php/php8.3-fpm.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 10
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 4
```

```bash
sudo systemctl restart php8.3-fpm
```

### 7.2 Nginx 站点

`/etc/nginx/sites-available/chaqin.conf`：

```nginx
server {
    listen 80;
    server_name chaqin.your-domain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name chaqin.your-domain.com;
    root /var/www/chaqin/public;
    index index.php;

    # SSL 证书（Let's Encrypt 申请，见 §8）
    ssl_certificate     /etc/letsencrypt/live/chaqin.your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/chaqin.your-domain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    client_max_body_size 20M;     # Excel 上传

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    # 微信 H5 兼容性
    fastcgi_buffer_size 16k;
    fastcgi_buffers 4 32k;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_read_timeout 60;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # 静态资源缓存
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff2?)$ {
        expires 7d;
        access_log off;
    }
}
```

启用：

```bash
sudo ln -s /etc/nginx/sites-available/chaqin.conf /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

---

## 8. HTTPS（Let's Encrypt）

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d chaqin.your-domain.com
# 按提示输入邮箱、同意条款
# 自动配置 80 → 443 重定向 + 续期
```

---

## 9. 微信内置浏览器适配（关键）

微信要求**所有网页必须 HTTPS** + **域名已备案**。否则链接发到群里点击会显示 "非官方页面"。

需要做：

1. **域名备案**：使用国内服务器需先 ICP 备案（云厂商一般提供入口）。
2. **业务域名配置**：登录公众号后台 → 设置 → 公众号设置 → 功能设置 → 业务域名，添加 `chaqin.your-domain.com`。
3. （可选）**JS 接口安全域名** 也加上。

---

## 10. 升级维护

```bash
cd /var/www/chaqin
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl reload php8.3-fpm
```

---

## 11. 备份

每日凌晨自动备份数据库到 `/var/backups/chaqin/`：

```bash
# /etc/cron.d/chaqin-backup
0 3 * * * root /usr/bin/mysqldump --single-transaction --routines chaqin | gzip > /var/backups/chaqin/db-$(date +\%F).sql.gz
0 4 * * 0 root find /var/backups/chaqin -name 'db-*.sql.gz' -mtime +30 -delete
```

Excel 导入模板在后台"学生管理 → Excel 导入"页一键下载，建议每月另存一份。

---

## 12. 监控（可选）

最小化监控：

- 用 [UptimeRobot](https://uptimerobot.com/) 监控 `https://chaqin.your-domain.com/up` 返回 200
- 服务器装 fail2ban 防 SSH 爆破
- Laravel 日志：`/var/www/chaqin/storage/logs/laravel.log`，建议接入 [Sentry](https://sentry.io) 或 [PaperTrail](https://papertrailapp.com)

---

## 附：Docker Compose（可选）

```yaml
# docker-compose.yml - 仅本地试用，生产仍建议用 systemd
version: "3.9"
services:
  app:
    image: php:8.3-fpm-alpine
    working_dir: /var/www/html
    volumes:
      - ./:/var/www/html
    command: sh -c "apk add --no-cache git icu-dev libzip-dev oniguruma-dev && docker-php-ext-install pdo_mysql bcmath intl gd zip && php artisan migrate --force && php-fpm"
  web:
    image: nginx:alpine
    ports: ["80:80", "443:443"]
    volumes:
      - ./:/var/www/html:ro
      - ./docker/nginx.conf:/etc/nginx/conf.d/default.conf:ro
    depends_on: [app]
  db:
    image: mysql:8
    environment:
      MYSQL_DATABASE: chaqin
      MYSQL_USER: chaqin
      MYSQL_PASSWORD: change_me
      MYSQL_ROOT_PASSWORD: change_me_root
    volumes: ["dbdata:/var/lib/mysql"]
volumes:
  dbdata:
```

---

部署完成 🎉 第一次访问 `https://chaqin.your-domain.com/admin/login` 用 `.env` 里 `ADMIN_EMAIL / ADMIN_PASSWORD` 登录，登录后立刻在"学生管理 → 管理员"处把默认管理员密码改掉（第一版未做改密 UI，临时方案：用 `php artisan tinker` 跑 `auth()->user()->update(['password'=>bcrypt('新密码')])`）。
