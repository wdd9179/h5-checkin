@extends('layouts.admin', ['title' => '今日签到'])

@section('content')
@php
    $rate = $stats['rate'] ?? 0;
    $absent = max(0, $stats['absent']);
    $bar = $stats['total'] > 0 ? round(($stats['signed'] / $stats['total']) * 100) : 0;
@endphp

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    {{-- 今日总览 --}}
    <div class="lg:col-span-2 bg-gradient-to-br from-slate-900 to-slate-700 text-white rounded-2xl p-6 shadow">
        <div class="flex items-center justify-between mb-1">
            <p class="text-sm text-slate-300">🌙 今日晚间签到</p>
            <p class="text-sm text-slate-300 font-mono">{{ \Carbon\Carbon::parse($date)->format('Y-m-d') }} {{ ['日','一','二','三','四','五','六'][\Carbon\Carbon::parse($date)->dayOfWeek] }}</p>
        </div>

        <div class="mt-4 grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="bg-white/10 rounded-xl p-3">
                <p class="text-xs text-slate-300">应签到</p>
                <p class="text-2xl font-bold mt-1">{{ $stats['total'] }}</p>
            </div>
            <div class="bg-white/10 rounded-xl p-3">
                <p class="text-xs text-slate-300">已签到</p>
                <p class="text-2xl font-bold mt-1 text-green-300">{{ $stats['signed'] }}</p>
            </div>
            <div class="bg-white/10 rounded-xl p-3">
                <p class="text-xs text-slate-300">迟到</p>
                <p class="text-2xl font-bold mt-1 text-amber-300">{{ $stats['late'] }}</p>
            </div>
            <div class="bg-white/10 rounded-xl p-3">
                <p class="text-xs text-slate-300">未签到</p>
                <p class="text-2xl font-bold mt-1 text-rose-300">{{ $absent }}</p>
            </div>
        </div>

        <div class="mt-5">
            <div class="flex justify-between text-xs text-slate-300 mb-1">
                <span>签到进度</span>
                <span class="font-mono">{{ $bar }}%</span>
            </div>
            <div class="h-2.5 bg-white/10 rounded-full overflow-hidden">
                <div class="h-full bg-gradient-to-r from-green-400 to-blue-400 transition-all" style="width: {{ $bar }}%"></div>
            </div>
        </div>

        <div class="mt-5 flex flex-wrap gap-2">
            <a href="{{ route('admin.checkins.detail', ['date' => $date]) }}" class="px-4 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-sm">查看签到详情</a>
            <a href="{{ route('admin.checkins.absent', ['date' => $date]) }}" class="px-4 py-2 rounded-lg bg-rose-500/20 hover:bg-rose-500/30 text-rose-100 text-sm">⚠️ 未签到 ({{ $absent }})</a>
            <a href="{{ route('admin.rules.share', ['date' => $date]) }}" class="px-4 py-2 rounded-lg bg-blue-500/20 hover:bg-blue-500/30 text-blue-100 text-sm">📤 生成签到链接</a>
        </div>
    </div>

    {{-- 今日签到规则 --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
        <h3 class="font-semibold text-slate-800 mb-3">今日规则</h3>
        @if ($rule)
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">开始</dt><dd class="font-mono">{{ \Str::substr($rule->start_time, 0, 5) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">正常截止</dt><dd class="font-mono">{{ \Str::substr($rule->normal_end_time, 0, 5) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">最晚截止</dt><dd class="font-mono">{{ \Str::substr($rule->final_end_time, 0, 5) }}</dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">状态</dt>
                    <dd>
                        <span class="px-2 py-0.5 rounded text-xs
                            {{ $rule->status === 'open' ? 'bg-green-100 text-green-700' : 'bg-slate-200 text-slate-600' }}">
                            {{ $rule->status === 'open' ? '开启' : '关闭' }}
                        </span>
                    </dd>
                </div>
            </dl>
            <a href="{{ route('admin.rules.edit', $rule) }}" class="mt-3 inline-block text-sm text-blue-600">编辑规则 →</a>
        @else
            <p class="text-sm text-slate-500">今日尚未创建规则</p>
            <a href="{{ route('admin.rules.create', ['date' => $date]) }}" class="mt-3 inline-block px-3 py-1.5 rounded-lg bg-blue-600 text-white text-sm">+ 创建今日规则</a>
        @endif
    </div>
</div>

{{-- 筛选日期 --}}
<div class="mt-6 bg-white rounded-2xl border border-slate-200 shadow-sm p-4 flex items-center gap-3">
    <form method="GET" action="{{ route('admin.dashboard') }}" class="flex items-center gap-2">
        <label class="text-sm text-slate-600">查看日期：</label>
        <input type="date" name="date" value="{{ $date }}"
            class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm">
        <button class="px-3 py-1.5 rounded-lg bg-slate-800 text-white text-sm">切换</button>
    </form>
    <span class="text-xs text-slate-400">|</span>
    <a href="{{ route('admin.dashboard') }}" class="text-sm text-slate-600 hover:text-slate-900">今天</a>
</div>
@endsection
