# 晚间签到 H5

一个跑在微信里的晚间签到系统：老师在微信群发链接 → 学生一键签到 → 后台看未签到名单 → 一键导出 Excel。

- 🎯 **核心场景**：班主任每天晚上 21:30 发起签到，30 秒内全班学生完成打卡
- 📱 **适配**：微信 iOS / Android 内置浏览器 + 手机竖屏
- 🛡️ **防代签**：每位学生绑定一个稳定的微信身份标识（第一版用 cookie UUID，第二版无缝切换真 OpenID）
- 🗄️ **数据可靠**：所有"是否已签到/是正常还是迟到"判定都在服务端，前端不可篡改

---

## 功能矩阵

| 角色 | 能力 |
| --- | --- |
| 学生 | 首次绑名 → 签到 → 查历史 |
| 老师 | 学生管理（CRUD / Excel 批量导入 / 禁用） / 签到规则 / 当日总览 / 详情 / 未签到名单 / 历史 / 统计 / Excel 导出 |

第一版交付范围对应你的需求文档"第一阶段【必须】"全部 12 项，第二/第三阶段（GPS、照片抽查、消息提醒）在数据库与代码结构上已预留扩展点。

---

## 技术栈

- Laravel 11（PHP 8.3+）
- Blade + Tailwind CSS 4 + Vite 8（移动端优先）
- SQLite（本地开发）/ MySQL 8（生产）
- maatwebsite/excel 4（xlsx 导入导出）
- 微信 OAuth：预留接口，第一版用 cookie 模拟

---

## 本地启动（5 分钟）

### 1. 准备环境

- PHP 8.2+  （含 pdo_sqlite / mbstring / gd / zip / curl / intl / bcmath）
- Composer 2
- Node.js 18+（仅首次构建前端需要）
- 可选：Git

### 2. 装依赖 + 配置

```bash
composer install
npm install
cp .env.example .env  # 或者直接复制 .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --force
php artisan db:seed --force   # 按 .env 的 ADMIN_* 创建管理员
npm run build                  # 生成 public/build 静态资源
```

### 3. 启服务

```bash
php artisan serve
```

浏览器打开：

- **学生端**：http://127.0.0.1:8000
- **后台**：http://127.0.0.1:8000/admin/login

默认管理员账号见 `.env` 的 `ADMIN_EMAIL` / `ADMIN_PASSWORD`。

### 4. 走一遍流程

1. 后台 → 学生管理 → 下载 Excel 模板 → 用 Excel 填 5 个学生 → 上传
2. 后台 → 签到规则 → 新建今日规则（默认 21:30~22:00 正常、~22:30 迟到）
3. 后台 → 签到规则 → 生成签到链接
4. 复制链接到另一个浏览器/无痕窗口打开（模拟"学生手机"）→ 选名字绑名 → 立即签到
5. 回到后台 → 今日总览 → 看已签到 / 未签到 / 签到详情
6. 后台 → 历史签到 → 看每个学生最近 7/14/30/60 天的签到率

---

## 目录结构

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/        # 后台
│   │   └── Student/      # 学生端
│   └── Middleware/
│       ├── EnsureAdmin.php
│       └── MockWechatOpenId.php
├── Imports/StudentsImport.php
├── Exports/
│   ├── StudentsExport.php
│   └── CheckInsExport.php
├── Models/
│   ├── User.php
│   ├── Student.php
│   ├── CheckIn.php
│   └── CheckInRule.php
└── Services/
    ├── CheckInService.php        # 业务核心：状态判定/统计/明细
    └── WechatBindingService.php  # OpenID ↔ 学生 绑定
config/chaqin.php                 # 微信 OAuth / 管理员初始账号配置
database/migrations/              # 4 张表
database/seeders/DatabaseSeeder.php
resources/views/                  # 所有 Blade
routes/web.php                    # 所有路由
scripts/                          # 本地调试用脚本（不参与部署）
```

---

## 关键设计决策

### 1. 防代签 — OpenID 绑定

```php
// WechatBindingService::bind()
// 同一时刻：
//   - 一个微信账号只能绑定一个学生
//   - 一个学生不能同时被多个微信占用
// DB 上 students.openid UNIQUE
```

学生第一次点链接 → 后台发 cookie（伪 openid） → 强制选名字绑名 → 之后同一浏览器/微信再来直接识别。

第一版 cookie 的"伪 OpenID"是随机 UUID；第二版切换真微信 OAuth 时，**业务代码零修改**（详见 `WECHAT_OAUTH.md`）。

### 2. 防重复签到 — 唯一约束

`check_ins` 表的 `(student_id, check_date)` UNIQUE。

服务层在事务内 `SELECT FOR UPDATE` 找到旧记录则更新、新记录则 insert。即使前端被绕过，DB 也会兜底。

### 3. 状态判定 — 服务端时间为准

```php
// CheckInRule::phaseAt(now())
//   now < start_time        → 未开始
//   start_time <= now <= normal_end → 正常
//   normal_end < now <= final_end   → 迟到
//   now > final_end         → 已结束
```

前端只能显示，不能决定。判断用 `Carbon::now()`，服务器时区（`config('app.timezone')`）为准。

### 4. IP + UA 记录

每次签到写入请求的 IP、User-Agent 留痕（schema 里有 latitude/longitude，第二版切 GPS 时直接用）。

### 5. 防 URL 篡改

- 学生身份识别只用 cookie（HttpOnly，浏览器自动带上），不用 URL 参数
- 签到提交走 POST，校验 CSRF token
- 服务端再校验 `openid → student_id` 的映射，不接受前端传的 student_id

---

## 数据库

| 表 | 关键字段 |
| --- | --- |
| `users` | id, name, email, password, role (admin) |
| `students` | id, name, student_no, class_name, dormitory, phone, openid (UNIQUE), status, bound_at |
| `check_in_rules` | id, check_date (UNIQUE), start_time, normal_end_time, final_end_time, status, note |
| `check_ins` | id, student_id, check_date, check_time, status, ip, user_agent, latitude, longitude — UNIQUE(student_id, check_date) |

详细迁移文件见 `database/migrations/`。

---

## 部署

见 [`DEPLOY.md`](DEPLOY.md)：从系统级依赖、Nginx、MySQL、HTTPS、备份、监控到故障排查全覆盖。

## 切换到真微信 OAuth

见 [`WECHAT_OAUTH.md`](WECHAT_OAUTH.md)：拿到公众号 AppID 后只需替换一个中间件 + 加一个回调控制器，业务代码不动。

---

## 常用命令

```bash
# 清缓存
php artisan optimize:clear

# 跑迁移
php artisan migrate

# 重新生成管理员（仅在 users 表为空时生效）
php artisan db:seed --force

# 进入 tinker 调试
php artisan tinker
>>> App\Models\Student::count()
>>> App\Models\CheckIn::whereDate('check_date', today())->get()
>>> auth()->user()->update(['password' => bcrypt('新密码')])   # 改密

# 前端实时开发
npm run dev    # vite dev server，热更新

# 一键重置 + 灌演示数据（仅本地）
php scripts/seed_demo.php    # 5 个学生 + 今日规则
php scripts/reset_e2e.php    # 清空 openid + 签到记录（保留学生）

# 端到端冒烟
php scripts/e2e_test.php     # 模拟学生：绑名 → 签到 → 重复签到被拒
php scripts/e2e_admin.php    # 模拟老师：登录 → 看板 → 导出
```

---

## 第二/三阶段预留（不做但已留好接口）

- **GPS 定位**：`check_ins.latitude / longitude` 已存在，前端只需调 `wx.getLocation`（需 JS-SDK 配置）
- **照片抽查**：`check_ins.attachments` 关系未建；如需可加 `check_in_photos` 表
- **宿舍二维码**：`dormitories` 表未建；students.dormitory 是字符串，简单方案足够
- **微信消息提醒**：`app/Services/WechatMessageService.php` 接口预留位置，调用模板消息/客服消息 API
- **连续迟到/缺勤预警**：用 `CheckIn::where('student_id', $id)->where('status', 'late')->where('check_date', '>=', now()->subDays(7))->count() >= N` 即可

---

## License

MIT
