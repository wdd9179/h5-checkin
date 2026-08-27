@extends('layouts.admin', ['title' => 'Excel 导入'])

@section('content')
<h1 class="text-xl font-bold text-slate-900 mb-4">📥 Excel 导入学生</h1>

<div class="bg-white rounded-xl border border-slate-200 p-5 max-w-xl">
    <h2 class="font-semibold text-slate-800 mb-2">使用步骤</h2>
    <ol class="text-sm text-slate-600 space-y-1.5 list-decimal list-inside">
        <li>下载导入模板（<code class="bg-slate-100 px-1.5 py-0.5 rounded text-xs">学生名单模板</code>）。</li>
        <li>在 Excel 中按列填写：<strong>姓名、学号、班级、宿舍、手机号</strong>。姓名必填，其余选填。</li>
        <li>保存为 <code class="bg-slate-100 px-1.5 py-0.5 rounded text-xs">.xlsx</code> 或 <code class="bg-slate-100 px-1.5 py-0.5 rounded text-xs">.csv</code>。</li>
        <li>上传文件，系统会按"姓名+学号"去重更新。</li>
    </ol>

    <a href="{{ route('admin.students.template') }}" class="mt-3 inline-block text-sm text-blue-600">📄 下载学生名单模板</a>

    <hr class="my-5 border-slate-200">

    <form method="POST" action="{{ route('admin.students.import.submit') }}" enctype="multipart/form-data" class="space-y-3">
        @csrf
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">选择文件</label>
            <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                class="block w-full text-sm text-slate-700 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
            @error('file')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <button class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium">开始导入</button>
    </form>
</div>
@endsection
