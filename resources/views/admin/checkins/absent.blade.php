@extends('layouts.admin', ['title' => '未签到名单'])

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h1 class="text-xl font-bold text-slate-900">⚠️ {{ \Carbon\Carbon::parse($date)->format('Y-m-d') }} 未签到</h1>
    <a href="{{ route('admin.checkins.detail', ['date' => $date]) }}" class="text-sm text-slate-600">← 返回签到详情</a>
</div>

<div class="bg-white rounded-xl border border-slate-200 p-5 mb-4">
    <p class="text-sm text-slate-600">共 <span class="font-bold text-rose-600 text-lg">{{ $rows->count() }}</span> 人未签到</p>
</div>

@if ($rows->isEmpty())
    <div class="bg-white rounded-xl border border-slate-200 p-12 text-center text-slate-500">
        <div class="text-4xl mb-2">🎉</div>
        全员已签到！
    </div>
@else
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
        @foreach ($rows as $row)
            <div class="bg-white rounded-xl border border-slate-200 p-3">
                <p class="font-semibold text-slate-900">{{ $row->student->name }}</p>
                <p class="text-xs text-slate-500 mt-0.5">{{ $row->student->class_name ?? '-' }} {{ $row->student->dormitory ?? '' }}</p>
                @if ($row->student->phone)
                    <p class="text-xs font-mono text-slate-500 mt-1">{{ $row->student->phone }}</p>
                @endif
            </div>
        @endforeach
    </div>

    <div class="mt-5 bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
        💡 提示：复制下方文字到微信群，催签到。
        <textarea class="mt-2 w-full bg-white border border-amber-200 rounded-lg p-2 text-xs font-mono" rows="4" readonly>⚠️ 今日晚间签到提醒

以下同学尚未完成签到，请尽快点击链接完成：
{{ rtrim(route('student.home', ['date' => $date]), '?date=' . $date) . '?date=' . $date }}

@foreach ($rows as $row)· {{ $row->student->name }}{{ $row->student->dormitory ? '（'.$row->student->dormitory.'）' : '' }}
@endforeach</textarea>
    </div>
@endif
@endsection
