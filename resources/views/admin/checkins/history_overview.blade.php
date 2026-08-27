@extends('layouts.admin', ['title' => '历史签到'])

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <h1 class="text-xl font-bold text-slate-900">📊 历史签到 · 全部学生</h1>
    <form method="GET" class="flex items-center gap-2 text-sm">
        <span class="text-slate-600">统计区间：</span>
        <select name="days" onchange="this.form.submit()" class="rounded-lg border border-slate-200 px-3 py-1.5">
            @foreach ([7, 14, 30, 60] as $d)
                <option value="{{ $d }}" @selected($days === $d)>最近 {{ $d }} 天</option>
            @endforeach
        </select>
    </form>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-600 text-xs">
            <tr>
                <th class="text-left px-3 py-2.5">学生</th>
                <th class="text-left px-3 py-2.5">班级</th>
                <th class="text-right px-3 py-2.5">正常</th>
                <th class="text-right px-3 py-2.5">迟到</th>
                <th class="text-right px-3 py-2.5">缺勤</th>
                <th class="text-right px-3 py-2.5">签到率</th>
                <th class="text-right px-3 py-2.5">操作</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($students as $idx => $s)
                @php $st = $stats[$idx]; @endphp
                <tr class="hover:bg-slate-50">
                    <td class="px-3 py-2.5 font-medium">{{ $s->name }}</td>
                    <td class="px-3 py-2.5 text-slate-500">{{ $s->class_name ?? '-' }} {{ $s->dormitory ?? '' }}</td>
                    <td class="px-3 py-2.5 text-right text-green-700 font-mono">{{ $st->normal }}</td>
                    <td class="px-3 py-2.5 text-right text-orange-700 font-mono">{{ $st->late }}</td>
                    <td class="px-3 py-2.5 text-right text-rose-700 font-mono">{{ $st->absent }}</td>
                    <td class="px-3 py-2.5 text-right">
                        <div class="inline-flex items-center gap-1.5">
                            <div class="w-16 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                <div class="h-full {{ $st->rate >= 90 ? 'bg-green-500' : ($st->rate >= 70 ? 'bg-amber-500' : 'bg-rose-500') }}" style="width: {{ $st->rate }}%"></div>
                            </div>
                            <span class="font-mono text-xs">{{ $st->rate }}%</span>
                        </div>
                    </td>
                    <td class="px-3 py-2.5 text-right">
                        <a href="{{ route('admin.checkins.history', ['student_id' => $s->id, 'days' => $days]) }}" class="text-blue-600 text-xs">明细</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-3 py-8 text-center text-slate-500">暂无学生</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
