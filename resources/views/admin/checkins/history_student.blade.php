@extends('layouts.admin', ['title' => $student->name . ' 签到明细'])

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h1 class="text-xl font-bold text-slate-900">
        {{ $student->name }}
        <span class="text-sm font-normal text-slate-500">· {{ $student->class_name ?? '-' }} {{ $student->dormitory ?? '' }}</span>
    </h1>
    <a href="{{ route('admin.checkins.history', ['days' => $days]) }}" class="text-sm text-slate-600">← 返回总览</a>
</div>

{{-- 统计 --}}
<div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-5">
    <div class="bg-white border border-slate-200 rounded-xl p-3 text-center">
        <p class="text-xs text-slate-500">应签到</p>
        <p class="text-xl font-bold mt-1">{{ $stats['total'] }}</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-3 text-center">
        <p class="text-xs text-slate-500">正常</p>
        <p class="text-xl font-bold mt-1 text-green-600">{{ $stats['normal'] }}</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-3 text-center">
        <p class="text-xs text-slate-500">迟到</p>
        <p class="text-xl font-bold mt-1 text-orange-600">{{ $stats['late'] }}</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-3 text-center">
        <p class="text-xs text-slate-500">缺勤</p>
        <p class="text-xl font-bold mt-1 text-rose-600">{{ $stats['absent'] }}</p>
    </div>
    <div class="bg-white border border-slate-200 rounded-xl p-3 text-center">
        <p class="text-xs text-slate-500">签到率</p>
        <p class="text-xl font-bold mt-1">{{ $stats['rate'] }}%</p>
    </div>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-600 text-xs">
            <tr>
                <th class="text-left px-3 py-2.5">日期</th>
                <th class="text-left px-3 py-2.5">状态</th>
                <th class="text-left px-3 py-2.5">签到时间</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @php
                $dateCursor = now()->subDays($days - 1)->toDateString();
                $end = now()->toDateString();
                $byDate = $records->keyBy(fn($r) => $r->check_date->toDateString());
            @endphp
            @for ($d = \Carbon\Carbon::parse($dateCursor); $d->lte(\Carbon\Carbon::parse($end)); $d->addDay())
                @php $r = $byDate->get($d->toDateString()); @endphp
                <tr class="hover:bg-slate-50">
                    <td class="px-3 py-2.5 font-mono">{{ $d->format('m-d') }} {{ ['日','一','二','三','四','五','六'][$d->dayOfWeek] }}</td>
                    <td class="px-3 py-2.5">
                        @if (!$r)
                            <span class="text-xs px-2 py-0.5 rounded bg-rose-100 text-rose-700">未签到</span>
                        @elseif ($r->status === 'normal')
                            <span class="text-xs px-2 py-0.5 rounded bg-green-100 text-green-700">正常</span>
                        @else
                            <span class="text-xs px-2 py-0.5 rounded bg-orange-100 text-orange-700">迟到</span>
                        @endif
                    </td>
                    <td class="px-3 py-2.5 font-mono">{{ $r?->check_time?->format('H:i:s') ?? '-' }}</td>
                </tr>
            @endfor
        </tbody>
    </table>
</div>
@endsection
