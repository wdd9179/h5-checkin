<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\WechatBindingService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function __construct(private readonly WechatBindingService $binding) {}

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status', 'all');

        $query = Student::query()->orderBy('class_name')->orderBy('dormitory')->orderBy('student_no');
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%$q%")
                  ->orWhere('student_no', 'like', "%$q%")
                  ->orWhere('dormitory', 'like', "%$q%")
                  ->orWhere('phone', 'like', "%$q%");
            });
        }
        if (in_array($status, ['active', 'disabled'], true)) {
            $query->where('status', $status);
        }

        $students = $query->paginate(30)->withQueryString();
        return view('admin.students.index', compact('students', 'q', 'status'));
    }

    public function create()
    {
        return view('admin.students.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateStudent($request);
        $data['status'] = 'active';
        $student = Student::create($data);
        return redirect()->route('admin.students.index')->with('success', "已添加学生：{$student->name}");
    }

    public function edit(Student $student)
    {
        return view('admin.students.edit', compact('student'));
    }

    public function update(Request $request, Student $student)
    {
        $data = $this->validateStudent($request, $student->id);
        $student->update($data);
        return redirect()->route('admin.students.index')->with('success', "已更新：{$student->name}");
    }

    public function destroy(Student $student)
    {
        $student->delete();
        return redirect()->route('admin.students.index')->with('success', '已删除');
    }

    public function toggleStatus(Student $student)
    {
        $student->status = $student->status === 'active' ? 'disabled' : 'active';
        $student->save();
        return back()->with('success', $student->status === 'active' ? '已启用' : '已禁用');
    }

    public function unbind(Student $student)
    {
        $this->binding->unbind($student);
        return back()->with('success', "已解除 {$student->name} 的微信绑定");
    }

    private function validateStudent(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name'        => ['required', 'string', 'max:50'],
            'student_no'  => ['nullable', 'string', 'max:50', Rule::unique('students', 'student_no')->ignore($id)->whereNotNull('student_no')],
            'class_name'  => ['nullable', 'string', 'max:50'],
            'dormitory'   => ['nullable', 'string', 'max:50'],
            'phone'       => ['nullable', 'string', 'max:30'],
        ]);
    }
}
