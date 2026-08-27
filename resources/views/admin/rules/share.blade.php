@extends('layouts.admin', ['title' => '生成签到链接'])

@section('content')
<h1 class="text-xl font-bold text-slate-900 mb-4">📤 签到链接</h1>

<div class="bg-white rounded-2xl border border-slate-200 p-6 max-w-2xl">
    <p class="text-sm text-slate-600 mb-3">
        日期 <span class="font-mono font-semibold">{{ $date }}</span> 的签到链接：
    </p>

    <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 font-mono text-sm break-all select-all" id="linkBox">{{ $url }}</div>

    <div class="mt-3 flex flex-wrap gap-2">
        <button data-copy="{{ $url }}" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm">📋 复制链接</button>
        <a href="{{ $url }}" target="_blank" class="px-4 py-2 rounded-lg bg-slate-200 text-slate-700 text-sm">在浏览器中打开</a>
    </div>

    <hr class="my-5 border-slate-200">

    <h2 class="font-semibold text-slate-800 mb-2">微信群发送模板</h2>
    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 text-sm text-slate-700 whitespace-pre-line" id="msgBox">🌙 今日晚间签到

请同学点击下面链接完成今日签到：

👉 {{ $url }}</div>
    <button data-copy="🌙 今日晚间签到\n\n请同学点击下面链接完成今日签到：\n\n👉 {{ $url }}" class="mt-2 px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm">📋 复制整段消息</button>

    <p class="text-xs text-slate-400 mt-4">提示：链接会被浏览器视为"未绑定"状态时引导学生首次绑名，绑定后该浏览器再次打开会自动识别学生。</p>
</div>
@endsection
