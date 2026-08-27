<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="format-detection" content="telephone=no">
    <meta name="theme-color" content="#1e293b">
    <title>{{ $title ?? '后台' }} - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-dvh bg-slate-50 text-slate-800">
    <header class="bg-slate-900 text-white sticky top-0 z-10 shadow">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2 font-bold">
                <span class="text-lg">🌙</span>
                <span>{{ config('app.name') }} · 后台</span>
            </a>
            <nav class="flex items-center gap-1 text-sm">
                <a href="{{ route('admin.dashboard') }}" class="px-2.5 py-1.5 rounded hover:bg-slate-700 {{ request()->routeIs('admin.dashboard') ? 'bg-slate-700' : '' }}">今日</a>
                <a href="{{ route('admin.students.index') }}" class="px-2.5 py-1.5 rounded hover:bg-slate-700 {{ request()->routeIs('admin.students.*') ? 'bg-slate-700' : '' }}">学生</a>
                <a href="{{ route('admin.rules.index') }}" class="px-2.5 py-1.5 rounded hover:bg-slate-700 {{ request()->routeIs('admin.rules.*') ? 'bg-slate-700' : '' }}">规则</a>
                <a href="{{ route('admin.checkins.history') }}" class="px-2.5 py-1.5 rounded hover:bg-slate-700 {{ request()->routeIs('admin.checkins.history') ? 'bg-slate-700' : '' }}">历史</a>
                <form method="POST" action="{{ route('admin.logout') }}" class="inline">
                    @csrf
                    <button class="px-2.5 py-1.5 rounded hover:bg-slate-700 text-rose-300">退出</button>
                </form>
            </nav>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-6">
        @if (session('success'))
            <div class="mb-4 rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif
        @yield('content')
    </main>
</body>
</html>
