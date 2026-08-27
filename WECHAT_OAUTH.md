# 切换到真微信 OAuth（第二版）

第一版系统用 cookie 里的随机 UUID 模拟"微信 OpenID"，接口已经预留切换位置——拿到公众号 AppID 后只要改两个地方即可。

---

## 前置准备

1. 注册**微信公众平台**账号（订阅号 / 服务号均可，服务号权限更全）
2. 完成主体认证（个人订阅号也可，但接口权限受限）
3. **业务域名**配置：公众号后台 → 设置 → 公众号设置 → 功能设置 → 业务域名，添加你的域名（需已 ICP 备案）
4. **网页授权域名**配置：开发 → 接口权限 → 网页授权 → 网页授权获取用户基本信息，添加 `chaqin.your-domain.com`（不加端口、不加 `https://`）
5. 拿到 `AppID` 和 `AppSecret`

---

## 第一步：填配置

编辑 `.env`：

```dotenv
WECHAT_OAUTH_ENABLED=true
WECHAT_OFFICIAL_APPID=wx0123456789abcdef
WECHAT_OFFICIAL_SECRET=0123456789abcdef0123456789abcdef
```

---

## 第二步：替换 Mock 中间件

打开 `app/Http/Middleware/MockWechatOpenId.php` 的注释说明，**把当前文件内容替换为真实实现**。下面是可直接用的实现：

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

/**
 * 微信网页授权（snsapi_userinfo），从 code 换 openid + 用户信息。
 * 第一版的 cookie 命名 (chaqin_openid) 与写入逻辑保持不变，
 * 业务代码（WechatBindingService）无需修改。
 */
class MockWechatOpenId
{
    public function handle(Request $request, Closure $next): Response
    {
        $cookieName = config('chaqin.mock_openid_cookie', 'chaqin_openid');
        $openid = $request->cookie($cookieName);

        if (empty($openid) && config('chaqin.wechat_oauth_enabled')) {
            // 没 cookie → 跳微信授权
            return $this->redirectToWechat($request);
        }

        if (empty($openid)) {
            // 关闭态：fallback 到 mock（兼容本地开发）
            $openid = 'mock_' . \Illuminate\Support\Str::random(40);
        }

        $request->attributes->set('mock_openid', $openid);

        $response = $next($request);

        if ($request->cookie($cookieName) !== $openid) {
            cookie()->queue(cookie()->forever($cookieName, $openid));
        }

        return $response;
    }

    private function redirectToWechat(Request $request): Response
    {
        $appid = config('chaqin.wechat_appid');
        $state = csrf_token();
        $redirect = route('wechat.callback'); // 见 §3
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize'
             . '?appid=' . urlencode($appid)
             . '&redirect_uri=' . urlencode($redirect)
             . '&response_type=code'
             . '&scope=snsapi_userinfo'
             . '&state=' . urlencode($state)
             . '#wechat_redirect';
        return redirect($url);
    }
}
```

> 注意：这里 `WechatBindingService` 读取的 attribute key `mock_openid` **保持不变**（只是 key 名沿用，值是真 openid）。第二版可以重命名为 `openid` 提高可读性，但是为了"切换不破坏业务代码"建议保留 key 名。

---

## 第三步：加回调路由

在 `routes/web.php` 顶部加：

```php
use App\Http\Controllers\WechatCallbackController;

Route::get('/wechat/callback', [WechatCallbackController::class, 'handle'])->name('wechat.callback');
```

新建 `app/Http/Controllers/WechatCallbackController.php`：

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WechatCallbackController extends Controller
{
    public function handle(Request $request)
    {
        $code = $request->query('code');
        abort_unless($code, 400, 'missing code');

        $resp = Http::timeout(10)->get('https://api.weixin.qq.com/sns/oauth2/access_token', [
            'appid'      => config('chaqin.wechat_appid'),
            'secret'     => config('chaqin.wechat_secret'),
            'code'       => $code,
            'grant_type' => 'authorization_code',
        ])->throw()->json();

        $openid = $resp['openid'] ?? null;
        abort_unless($openid, 400, 'no openid from wechat');

        // 写 cookie 后跳回原来的页面
        $cookieName = config('chaqin.mock_openid_cookie', 'chaqin_openid');
        return redirect('/')
            ->withCookie(cookie()->forever($cookieName, $openid));
    }
}
```

---

## 第四步：可选——把学生姓名/头像预填

拿到 access_token 后可以再请求一次：

```php
$userInfo = Http::get('https://api.weixin.qq.com/sns/userinfo', [
    'access_token' => $resp['access_token'],
    'openid'       => $openid,
    'lang'         => 'zh_CN',
])->json();
```

可以把 `$userInfo['nickname']`、`$userInfo['headimgurl']` 暂存到 session，在首次绑名页下拉旁边显示"微信昵称 XXX"，帮助学生确认选对了自己。这只是体验优化，不影响绑定逻辑。

---

## 第五步：测试

1. 在手机微信里给"文件传输助手"发链接 `https://chaqin.your-domain.com`
2. 点击链接 → 跳到 `open.weixin.qq.com` 授权页 → 同意
3. 跳回系统 → 显示"晚间签到 / 确认身份"页

如果只看到 404：检查公众号后台"网页授权域名"是否真的填的**裸域名**（不带 `https://`）。

如果看到 redirect_uri 错误：检查 `route('wechat.callback')` 生成的 URL 是否与公众号后台填的域名一致。

---

## 第六步：清理

切换稳定后，可以把 `MockWechatOpenId` 类重命名为 `WechatOpenId`，attribute key 同步改名为 `openid`。改动是 grep+replace 全项目字符串级别，业务代码不用动。

---

## FAQ

**Q: 公众号是订阅号可以吗？**
A: 个人订阅号可以获取 openid（scope=snsapi_base），但拿不到昵称/头像。本系统最低只需要 openid，所以订阅号足够。

**Q: 必须备案吗？**
A: 国内服务器部署 + 微信内访问，必须 ICP 备案。海外服务器 + 海外域名也行，但用户访问会慢且微信可能提示风险。

**Q: AppSecret 泄露怎么办？**
A: 立即在公众号后台重置。生产环境通过 `php artisan config:cache` 缓存配置，`.env` 设置 `chmod 600`。

**Q: openid 唯一性？**
A: 同一公众号下，openid 对每个用户唯一、永远不变。可以安全用作主键。
