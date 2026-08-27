<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 微信 OAuth 配置
    |--------------------------------------------------------------------------
    | 第一版无 AppID 时，wechat_oauth_enabled=false，自动启用 mock 中间件。
    | 拿到公众号 AppID/Secret 后：
    |   1) 填 WECHAT_OFFICIAL_APPID / WECHAT_OFFICIAL_SECRET
    |   2) WECHAT_OAUTH_ENABLED=true
    |   3) 在 routes/web.php 把 'mock.wechat.openid' 替换为真实授权跳转
    */
    'wechat_oauth_enabled' => env('WECHAT_OAUTH_ENABLED', false),
    'wechat_appid'         => env('WECHAT_OFFICIAL_APPID'),
    'wechat_secret'        => env('WECHAT_OFFICIAL_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Mock OpenID cookie
    |--------------------------------------------------------------------------
    */
    'mock_openid_cookie'   => env('MOCK_OPENID_COOKIE', 'chaqin_openid'),

    /*
    |--------------------------------------------------------------------------
    | 管理员初始账号
    |--------------------------------------------------------------------------
    | 首次启动时如果 users 表为空，自动用以下凭据创建超级管理员。
    */
    'admin' => [
        'name'     => env('ADMIN_NAME', '管理员'),
        'email'    => env('ADMIN_EMAIL', 'admin@chaqin.local'),
        'password' => env('ADMIN_PASSWORD', 'admin123456'),
    ],
];
