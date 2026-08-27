@extends('layouts.admin', ['title' => '签到详情'])

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h1 class="text-xl font-bold text-slate-900">签到详情 · {{ \Carbon\Carbon::parse($date)->format('Y-m-d') }}</h1>
    <div class="flex gap-2 text-sm">
        <a href="{{ route('admin.checkins.absent', ['date' => $date]) }}" class="px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 border border-rose-200">⚠️ 未签到 ({{ $stats['absent'] }})</a>
        <a href="{{ route('admin.checkins.export', ['date' => $date]) }}" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">📤 导出 Excel</a>
    </div>
</div>

{{-- 筛选 + 统计 --}}
<div class="bg-white rounded-xl border border-slate-200 p-4 mb-4 flex flex-wrap items-center gap-3 text-sm">
    <div class="flex gap-2">
        @php $filters = ['all' => '全部', 'normal' => '正常', 'late' => '迟到', 'absent' => '未签到']; @endphp
        @foreach ($filters as $k => $v)
            <a href="{{ route('admin.checkins.detail', ['date' => $date, 'filter' => $k]) }}"
                class="px-3 py-1 rounded-lg {{ $filter === $k ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                {{ $v }}
            </a>
        @endforeach
    </div>
    <span class="text-xs text-slate-400 ml-auto">
        应签 {{ $stats['total'] }} · 已签 {{ $stats['signed'] }} · 迟到 {{ $stats['late'] }} · 未签 {{ $stats['absent'] }}
    </span>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-600 text-xs">
            <tr>
                <th class="text-left px-3 py-2.5">学生</th>
                <th class="text-left px-3 py-2.5">学号</th>
                <th class="text-left px-3 py-2.5">班级/宿舍</th>
                <th class="text-left px-3 py-2.5">状态</th>
                <th class="text-left px-3 py-2.5">签到时间</th>
                <th class="text-left px-3 py-2.5">IP</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($rows as $row)
                @php $s = $row->student; $ci = $row->check_in; @endphp
                <tr class="hover:bg-slate-50">
                    <td class="px-3 py-2.5 font-medium">{{ $s->name }}</td>
                    <td class="px-3 py-2.5 text-slate-500">{{ $s->student_no ?? '-' }}</td>
                    <td class="px-3 py-2.5 text-slate-500">{{ $s->class_name ?? '-' }} {{ $s->dormitory ?? '' }}</td>
                    <td class="px-3 py-2.5">
                        @if (!$ci)
                            <span class="text-xs px-2 py-0.5 rounded bg-rose-100 text-rose-700">🔴 未签到</span>
                        @elseif ($ci->status === 'normal')
                            <span class="text-xs px-2 py-0.5 rounded bg-green-100 text-green-700">🟢 正常</span>
                        @else
                            <span class="text-xs px-2 py-0.5 rounded bg-orange-100 text-orange-700">🟠 迟到</span>
                        @endif
                    </td>
                    <td class="px-3 py-2.5 font-mono">{{ $ci?->check_time?->format('H:i:s') ?? '-' }}</td>
                    <td class="px-3 py-2.5 font-mono text-xs text-slate-500">{{ $ci?->ip ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-3 py-8 text-center text-slate-500">无数据</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<p class="mt-4">
    <a href="{{ route('admin.dashboard', ['date' => $date]) }}" class="text-sm text-slate-600">← 返回总览</a>
</p>
@endsection
