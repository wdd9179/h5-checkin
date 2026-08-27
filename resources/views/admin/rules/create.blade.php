@extends('layouts.admin', ['title' => isset($rule) ? '编辑规则' : '新建规则'])

@section('content')
<h1 class="text-xl font-bold text-slate-900 mb-4">
    {{ isset($rule) ? '编辑规则：'.$rule->check_date->format('Y-m-d') : '新建签到规则' }}
</h1>

<form method="POST" action="{{ isset($rule) ? route('admin.rules.update', $rule) : route('admin.rules.store') }}"
    class="bg-white rounded-xl border border-slate-200 p-5 max-w-2xl space-y-4">
    @csrf
    @if (isset($rule))
        @method('PUT')
    @else
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">签到日期 <span class="text-rose-500">*</span></label>
            <input type="date" name="check_date" required value="{{ old('check_date', $date) }}"
                class="rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 focus:bg-white">
            @error('check_date')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
            <p class="text-xs text-slate-500 mt-1">每个日期只能有一条规则，重复提交将覆盖。</p>
        </div>
    @endif

    <div class="grid grid-cols-3 gap-3">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">开始时间 <span class="text-rose-500">*</span></label>
            <input type="time" name="start_time" required
                value="{{ old('start_time', $rule->start_time ?? ($defaults['start_time'] ?? '21:30')) }}"
                class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 focus:bg-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">正常截止 <span class="text-rose-500">*</span></label>
            <input type="time" name="normal_end_time" required
                value="{{ old('normal_end_time', $rule->normal_end_time ?? ($defaults['normal_end_time'] ?? '22:00')) }}"
                class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 focus:bg-white">
            <p class="text-xs text-slate-500 mt-1">在此之前签到 = 正常</p>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">最终截止 <span class="text-rose-500">*</span></label>
            <input type="time" name="final_end_time" required
                value="{{ old('final_end_time', $rule->final_end_time ?? ($defaults['final_end_time'] ?? '22:30')) }}"
                class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 focus:bg-white">
            <p class="text-xs text-slate-500 mt-1">超时 = 不可签到</p>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">状态</label>
            <select name="status" class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm">
                <option value="open" @selected(old('status', $rule->status ?? 'open') === 'open')>开启</option>
                <option value="closed" @selected(old('status', $rule->status ?? 'open') === 'closed')>关闭</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">备注</label>
            <input type="text" name="note" value="{{ old('note', $rule->note ?? '') }}" maxlength="255"
                class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm" placeholder="可选">
        </div>
    </div>

    <div class="pt-2 flex items-center gap-2">
        <button class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium">保存</button>
        <a href="{{ route('admin.rules.index') }}" class="px-4 py-2 rounded-lg bg-slate-200 text-slate-700 text-sm">取消</a>
    </div>
</form>
@endsection
