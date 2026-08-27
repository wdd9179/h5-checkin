<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台登录 - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh bg-slate-100 flex items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-6">
            <div class="text-4xl mb-2">🌙</div>
            <h1 class="text-2xl font-bold text-slate-900">{{ config('app.name') }} · 后台</h1>
            <p class="text-sm text-slate-500 mt-1">仅限管理员登录</p>
        </div>

        <div class="bg-white rounded-2xl shadow border border-slate-200 p-6">
            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-sm text-rose-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.submit') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">邮箱</label>
                    <input type="email" name="email" required autofocus value="{{ old('email') }}"
                        class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 focus:bg-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">密码</label>
                    <input type="password" name="password" required
                        class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 focus:bg-white">
                </div>
                <button type="submit" class="w-full rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold py-2.5 text-sm">
                    登录
                </button>
            </form>
        </div>

        @if (app()->isLocal())
            <p class="text-xs text-center text-slate-400 mt-4">
                开发环境默认账号：{{ config('chaqin.admin.email') }} / {{ config('chaqin.admin.password') }}
            </p>
        @endif
    </div>
</body>
</html>
