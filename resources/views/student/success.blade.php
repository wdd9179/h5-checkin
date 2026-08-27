@extends('layouts.student', ['title' => '签到成功'])

@section('content')
<div class="flex-1 flex flex-col items-center justify-center -mt-8">
    <div class="bg-white rounded-3xl shadow-md border border-gray-100 p-8 w-full text-center">
        <div class="text-6xl mb-3">🎉</div>
        <h1 class="text-2xl font-bold text-gray-900">签到成功！</h1>

        <div class="mt-6 text-left bg-gray-50 rounded-xl p-4 space-y-1.5">
            <p class="text-sm text-gray-500">姓名</p>
            <p class="text-lg font-semibold text-gray-900">{{ $student->name }}</p>
            <p class="text-sm text-gray-500 mt-3">签到时间</p>
            <p class="text-2xl font-mono font-bold tabular-nums text-gray-900">{{ $checkIn->check_time->format('H:i:s') }}</p>
            <p class="text-sm text-gray-500 mt-3">签到状态</p>
            <p class="text-base font-semibold {{ $checkIn->status === 'normal' ? 'text-green-600' : 'text-orange-600' }}">
                {{ $checkIn->status === 'normal' ? '🟢 正常签到' : '🟠 迟到签到' }}
            </p>
        </div>

        <p class="text-sm text-gray-500 mt-4">今日签到已经完成。</p>

        <a href="{{ route('student.checkin') }}"
            class="mt-6 block w-full rounded-xl bg-blue-50 text-blue-700 font-medium py-3 text-center">
            返回签到页
        </a>
    </div>

    <a href="{{ route('student.history') }}" class="mt-4 text-sm text-blue-600">查看历史记录 →</a>
</div>
@endsection
