<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="format-detection" content="telephone=no">
    <meta name="theme-color" content="#2563eb">
    <title>{{ $title ?? '晚间签到' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-dvh bg-gradient-to-b from-blue-50 to-white">
    <main class="mx-auto max-w-md min-h-dvh px-4 py-6 flex flex-col">
        @if (session('success'))
            <div class="mb-4 rounded-xl bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-4 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif
        @yield('content')
    </main>
    <footer class="text-center text-xs text-gray-400 py-4">
        晚间签到 · {{ date('Y') }}
    </footer>
</body>
</html>
