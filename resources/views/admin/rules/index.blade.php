@extends('layouts.admin', ['title' => '签到规则'])

@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-slate-900">签到规则</h1>
    <a href="{{ route('admin.rules.create') }}" class="px-3 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm">+ 新建规则</a>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-600 text-xs">
            <tr>
                <th class="text-left px-3 py-2.5">日期</th>
                <th class="text-left px-3 py-2.5">开始</th>
                <th class="text-left px-3 py-2.5">正常截止</th>
                <th class="text-left px-3 py-2.5">最终截止</th>
                <th class="text-left px-3 py-2.5">状态</th>
                <th class="text-left px-3 py-2.5">备注</th>
                <th class="text-right px-3 py-2.5">操作</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($rules as $r)
                <tr class="hover:bg-slate-50">
                    <td class="px-3 py-2.5 font-medium">{{ $r->check_date->format('Y-m-d') }}</td>
                    <td class="px-3 py-2.5 font-mono">{{ \Str::substr($r->start_time, 0, 5) }}</td>
                    <td class="px-3 py-2.5 font-mono">{{ \Str::substr($r->normal_end_time, 0, 5) }}</td>
                    <td class="px-3 py-2.5 font-mono">{{ \Str::substr($r->final_end_time, 0, 5) }}</td>
                    <td class="px-3 py-2.5">
                        <span class="text-xs px-2 py-0.5 rounded {{ $r->status === 'open' ? 'bg-green-100 text-green-700' : 'bg-slate-200 text-slate-600' }}">
                            {{ $r->status === 'open' ? '开启' : '关闭' }}
                        </span>
                    </td>
                    <td class="px-3 py-2.5 text-slate-500">{{ $r->note ?? '-' }}</td>
                    <td class="px-3 py-2.5 text-right whitespace-nowrap">
                        <a href="{{ route('admin.rules.edit', $r) }}" class="text-blue-600 text-xs mr-2">编辑</a>
                        <a href="{{ route('admin.rules.share', ['date' => $r->check_date->toDateString()]) }}" class="text-emerald-600 text-xs mr-2">链接</a>
                        <form method="POST" action="{{ route('admin.rules.destroy', $r) }}" class="inline" onsubmit="return confirm('确认删除？')">
                            @csrf @method('DELETE')
                            <button class="text-rose-600 text-xs">删除</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-3 py-8 text-center text-slate-500">暂无规则，请先创建今日签到</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $rules->links() }}</div>
@endsection
