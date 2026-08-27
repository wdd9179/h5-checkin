<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * 模拟微信 OAuth：每个浏览器/设备分配一个稳定的伪 OpenID，存入 cookie。
 * 第二版切到真微信 OAuth 时，仅需在本中间件把生成逻辑替换成
 *   1) 跳转到微信授权页 -> 回调 -> 用 code 换 openid
 *   2) 后续 cookie 仍存 openid 字符串，业务代码不变
 */
class MockWechatOpenId
{
    public function handle(Request $request, Closure $next): Response
    {
        $cookieName = config('chaqin.mock_openid_cookie', 'chaqin_openid');
        $openid = $request->cookie($cookieName);

        if (empty($openid)) {
            $openid = 'mock_' . Str::random(40);
        }

        // 注入到请求，供控制器/服务读取
        $request->attributes->set('mock_openid', $openid);

        $response = $next($request);

        // 长期有效（10 年），同浏览器再来仍能识别
        if ($request->cookie($cookieName) !== $openid) {
            cookie()->queue(cookie()->forever($cookieName, $openid));
        }

        return $response;
    }
}
