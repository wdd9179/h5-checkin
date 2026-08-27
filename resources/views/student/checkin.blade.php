@extends('layouts.student', ['title' => '晚间签到'])

@section('content')
@php
    $phaseColor = $rule?->phaseColor($phase) ?? 'gray';
    $phaseLabel = $rule?->phaseLabel($phase) ?? '今日未开启签到';
    $colorMap = [
        'green' => ['bg' => 'bg-green-50', 'border' => 'border-green-200', 'text' => 'text-green-700', 'dot' => 'bg-green-500', 'btn' => 'bg-green-600 hover:bg-green-700'],
        'orange'=> ['bg' => 'bg-orange-50','border' => 'border-orange-200','text' => 'text-orange-700','dot' => 'bg-orange-500','btn' => 'bg-orange-600 hover:bg-orange-700'],
        'red'   => ['bg' => 'bg-red-50',   'border' => 'border-red-200',   'text' => 'text-red-700',   'dot' => 'bg-red-500',   'btn' => 'bg-gray-400 cursor-not-allowed'],
        'gray'  => ['bg' => 'bg-gray-50',  'border' => 'border-gray-200',  'text' => 'text-gray-600',  'dot' => 'bg-gray-400',  'btn' => 'bg-gray-400 cursor-not-allowed'],
    ];
    $c = $colorMap[$phaseColor] ?? $colorMap['gray'];
@endphp

<div class="flex-1 flex flex-col">
    {{-- 顶部 --}}
    <div class="text-center mt-4 mb-6">
        <div class="text-4xl mb-2">🌙</div>
        <h1 class="text-xl font-bold text-gray-900">晚间签到</h1>
    </div>

    {{-- 身份卡 --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-4">
        <div class="text-center">
            <p class="text-xs text-gray-500 mb-1">签到人</p>
            <p class="text-2xl font-bold text-gray-900">{{ $student->name }}</p>
            @if ($student->class_name || $student->dormitory)
                <p class="text-sm text-gray-500 mt-1">
                    {{ $student->class_name }}{{ $student->dormitory ? ' · '.$student->dormitory : '' }}
                </p>
            @endif
        </div>
    </div>

    {{-- 日期 + 当前时间 --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-4">
        <div class="text-center text-sm text-gray-600">
            <div>{{ \Carbon\Carbon::parse($date)->format('Y年m月d日') }} {{ ['日','一','二','三','四','五','六'][\Carbon\Carbon::parse($date)->dayOfWeek] }}</div>
            <div class="mt-2 font-mono text-3xl font-semibold text-gray-900 tabular-nums" id="nowClock">--:--:--</div>
        </div>
    </div>

    {{-- 状态卡 --}}
    <div class="rounded-2xl border px-4 py-3 mb-4 {{ $c['bg'] }} {{ $c['border'] }}">
        <div class="flex items-center gap-2 {{ $c['text'] }}">
            <span class="w-2 h-2 rounded-full {{ $c['dot'] }}"></span>
            <span class="font-medium text-sm">
                @if ($phase === 'not_started') ⏰ @elseif ($phase === 'closed') ⚠️ @else 🟢 @endif
                {{ $phaseLabel }}
            </span>
        </div>
    </div>

    {{-- 已签到状态卡 --}}
    @if ($existing)
        <div class="rounded-2xl bg-blue-50 border border-blue-200 p-5 mb-4">
            <p class="text-sm text-blue-700 font-medium">✅ 今日已签到</p>
            <p class="mt-2 text-sm text-gray-700">签到时间：<span class="font-mono font-semibold">{{ $existing->check_time->format('H:i:s') }}</span></p>
            <p class="mt-1 text-xs text-gray-500">不允许重复签到</p>
        </div>
        <a href="{{ route('student.history') }}" class="text-center text-sm text-blue-600 mt-2 block">查看我的签到记录 →</a>
    @else
        {{-- 签到按钮 --}}
        <form method="POST" action="{{ route('student.checkin.submit') }}">
            @csrf
            <button type="submit"
                @disabled($phase === 'not_started' || $phase === 'closed')
                class="w-full rounded-2xl text-white font-bold text-lg py-5 shadow-md transition {{ $c['btn'] }}">
                @if ($phase === 'not_started')
                    签到未开始
                @elseif ($phase === 'closed')
                    签到已结束
                @else
                    立即签到
                @endif
            </button>
        </form>

        @if ($rule)
            <p class="text-center text-xs text-gray-500 mt-3">
                正常签到：{{ \Str::substr($rule->start_time, 0, 5) }} ~ {{ \Str::substr($rule->normal_end_time, 0, 5) }} ·
                截止：{{ \Str::substr($rule->final_end_time, 0, 5) }}
            </p>
        @endif
    @endif

    <a href="{{ route('student.history') }}" class="text-center text-sm text-gray-500 mt-4 block">查看我的历史签到</a>
</div>

<script>
(function(){
  const el = document.getElementById('nowClock');
  if (!el) return;
  const tick = () => {
    const d = new Date();
    const z = n => String(n).padStart(2, '0');
    el.textContent = `${z(d.getHours())}:${z(d.getMinutes())}:${z(d.getSeconds())}`;
  };
  tick();
  setInterval(tick, 1000);
})();
</script>
@endsection
