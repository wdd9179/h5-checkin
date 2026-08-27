@extends('layouts.admin', ['title' => '学生管理'])

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h1 class="text-xl font-bold text-slate-900">学生管理</h1>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.students.create') }}" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm">+ 添加学生</a>
        <a href="{{ route('admin.students.import') }}" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm">📥 Excel 导入</a>
        <a href="{{ route('admin.students.export', request()->query()) }}" class="px-3 py-1.5 rounded-lg bg-slate-700 hover:bg-slate-800 text-white text-sm">📤 导出 Excel</a>
    </div>
</div>

{{-- 搜索 --}}
<form method="GET" class="bg-white rounded-xl border border-slate-200 p-3 mb-4 flex flex-wrap items-center gap-2 text-sm">
    <input type="text" name="q" value="{{ $q }}" placeholder="姓名 / 学号 / 宿舍 / 手机号" class="rounded-lg border border-slate-200 px-3 py-1.5 flex-1 min-w-[200px]">
    <select name="status" class="rounded-lg border border-slate-200 px-3 py-1.5">
        <option value="all" @selected($status === 'all')>全部状态</option>
        <option value="active" @selected($status === 'active')>正常</option>
        <option value="disabled" @selected($status === 'disabled')>已禁用</option>
    </select>
    <button class="px-3 py-1.5 rounded-lg bg-slate-800 text-white">搜索</button>
    @if ($q || $status !== 'all')
        <a href="{{ route('admin.students.index') }}" class="text-slate-500 text-sm">清空</a>
    @endif
</form>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-600 text-xs">
            <tr>
                <th class="text-left px-3 py-2.5">姓名</th>
                <th class="text-left px-3 py-2.5">学号</th>
                <th class="text-left px-3 py-2.5">班级</th>
                <th class="text-left px-3 py-2.5">宿舍</th>
                <th class="text-left px-3 py-2.5">手机</th>
                <th class="text-left px-3 py-2.5">绑定</th>
                <th class="text-left px-3 py-2.5">状态</th>
                <th class="text-right px-3 py-2.5">操作</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($students as $s)
                <tr class="hover:bg-slate-50">
                    <td class="px-3 py-2.5 font-medium">{{ $s->name }}</td>
                    <td class="px-3 py-2.5 text-slate-500">{{ $s->student_no ?? '-' }}</td>
                    <td class="px-3 py-2.5 text-slate-500">{{ $s->class_name ?? '-' }}</td>
                    <td class="px-3 py-2.5 text-slate-500">{{ $s->dormitory ?? '-' }}</td>
                    <td class="px-3 py-2.5 text-slate-500">{{ $s->phone ?? '-' }}</td>
                    <td class="px-3 py-2.5">
                        @if ($s->openid)
                            <span class="text-xs px-2 py-0.5 rounded bg-blue-100 text-blue-700">已绑定</span>
                        @else
                            <span class="text-xs px-2 py-0.5 rounded bg-slate-100 text-slate-500">未绑定</span>
                        @endif
                    </td>
                    <td class="px-3 py-2.5">
                        @if ($s->status === 'active')
                            <span class="text-xs px-2 py-0.5 rounded bg-green-100 text-green-700">正常</span>
                        @else
                            <span class="text-xs px-2 py-0.5 rounded bg-rose-100 text-rose-700">已禁用</span>
                        @endif
                    </td>
                    <td class="px-3 py-2.5 text-right whitespace-nowrap">
                        <a href="{{ route('admin.students.edit', $s) }}" class="text-blue-600 text-xs mr-2">编辑</a>
                        @if ($s->openid)
                            <form method="POST" action="{{ route('admin.students.unbind', $s) }}" class="inline" onsubmit="return confirm('确认解除该学生的微信绑定？')">
                                @csrf
                                <button class="text-amber-600 text-xs mr-2">解绑</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('admin.students.toggle', $s) }}" class="inline">
                            @csrf
                            <button class="text-slate-600 text-xs mr-2">{{ $s->status === 'active' ? '禁用' : '启用' }}</button>
                        </form>
                        <form method="POST" action="{{ route('admin.students.destroy', $s) }}" class="inline" onsubmit="return confirm('确认删除 {{ $s->name }} ？该操作不可恢复。')">
                            @csrf @method('DELETE')
                            <button class="text-rose-600 text-xs">删除</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="px-3 py-8 text-center text-slate-500">暂无学生，请先添加或导入</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $students->links() }}</div>
@endsection
