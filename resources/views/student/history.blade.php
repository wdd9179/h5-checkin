@extends('layouts.student', ['title' => '我的签到'])

@section('content')
<div class="flex-1">
    <h1 class="text-xl font-bold text-gray-900 mb-1">我的签到</h1>
    <p class="text-sm text-gray-500 mb-5">{{ $student->name }} · 最近 30 天</p>

    @if ($records->isEmpty())
        <div class="bg-white rounded-2xl p-8 text-center text-gray-500">
            <div class="text-4xl mb-2">📭</div>
            暂无签到记录
        </div>
    @else
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden divide-y divide-gray-100">
            @foreach ($records as $r)
                <div class="px-4 py-3 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $r->check_date->format('m-d') }} {{ ['日','一','二','三','四','五','六'][$r->check_date->dayOfWeek] }}</p>
                        <p class="text-xs text-gray-500 font-mono mt-0.5">{{ $r->check_time->format('H:i') }}</p>
                    </div>
                    <span class="text-xs px-2.5 py-1 rounded-full
                        {{ $r->status === 'normal' ? 'bg-green-100 text-green-700' : '' }}
                        {{ $r->status === 'late' ? 'bg-orange-100 text-orange-700' : '' }}">
                        {{ $r->status === 'normal' ? '正常' : '迟到' }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif

    <a href="{{ route('student.checkin') }}" class="block text-center text-sm text-blue-600 mt-6">← 返回签到</a>
</div>
@endsection
