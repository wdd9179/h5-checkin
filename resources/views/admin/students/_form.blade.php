@csrf
@if (isset($student) && $student)
    @method('PUT')
@endif

<div class="space-y-4 max-w-xl">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">姓名 <span class="text-rose-500">*</span></label>
        <input type="text" name="name" required value="{{ old('name', $student->name ?? '') }}"
            class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 focus:bg-white">
        @error('name')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">学号</label>
            <input type="text" name="student_no" value="{{ old('student_no', $student->student_no ?? '') }}"
                class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 focus:bg-white">
            @error('student_no')<p class="text-rose-600 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">班级</label>
            <input type="text" name="class_name" value="{{ old('class_name', $student->class_name ?? '') }}"
                class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 focus:bg-white">
        </div>
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">宿舍</label>
            <input type="text" name="dormitory" value="{{ old('dormitory', $student->dormitory ?? '') }}"
                class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 focus:bg-white">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">手机号</label>
            <input type="text" name="phone" value="{{ old('phone', $student->phone ?? '') }}"
                class="block w-full rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 focus:bg-white">
        </div>
    </div>

    <div class="pt-2 flex items-center gap-2">
        <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium">保存</button>
        <a href="{{ route('admin.students.index') }}" class="px-4 py-2 rounded-lg bg-slate-200 text-slate-700 text-sm">取消</a>
    </div>
</div>
