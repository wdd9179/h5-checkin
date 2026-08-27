@extends('layouts.student', ['title' => '确认身份'])

@section('content')
<div class="flex-1 flex flex-col">
    {{-- 顶部 logo --}}
    <div class="text-center mt-6 mb-8">
        <div class="text-5xl mb-3">🌙</div>
        <h1 class="text-2xl font-bold text-gray-900">晚间签到</h1>
        <p class="text-sm text-gray-500 mt-1">欢迎使用签到系统</p>
    </div>

    {{-- 微信身份 --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <div class="flex items-center gap-2 text-sm text-gray-600 mb-4">
            <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
            当前微信：<span class="font-mono text-gray-800 truncate" title="{{ $openid }}">{{ \Str::limit($openid, 28, '…') }}</span>
        </div>

        <form method="POST" action="{{ route('student.bind.submit') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">请选择你的姓名</label>
                @if ($students->isEmpty())
                    <p class="text-sm text-rose-600">暂无可绑定的学生，请联系老师开通账号。</p>
                @else
                    <select name="student_id" required
                        class="block w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3.5 text-base focus:border-blue-500 focus:ring-2 focus:ring-blue-100 focus:bg-white transition">
                        <option value="">-- 请选择 --</option>
                        @foreach ($students as $s)
                            <option value="{{ $s->id }}" @selected(old('student_id') == $s->id)>
                                {{ $s->name }}{{ $s->class_name ? ' · '.$s->class_name : '' }}{{ $s->dormitory ? ' · '.$s->dormitory : '' }}{{ $s->student_no ? ' · '.$s->student_no : '' }}
                            </option>
                        @endforeach
                    </select>
                @endif
                @error('student_id')
                    <p class="text-sm text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-lg bg-amber-50 border border-amber-100 px-3 py-2 text-xs text-amber-700">
                ⚠️ 首次绑定后，该微信账号只能签到对应学生。请确认姓名正确再提交。
            </div>

            <button type="submit"
                class="w-full rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 disabled:bg-gray-300 text-white font-semibold py-3.5 text-base shadow-sm transition">
                确认身份
            </button>
        </form>
    </div>

    <p class="text-center text-xs text-gray-400 mt-6">
        如姓名列表中找不到自己，请联系班主任。
    </p>
</div>
@endsection
