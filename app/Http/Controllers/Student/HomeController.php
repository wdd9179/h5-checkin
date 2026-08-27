<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\CheckInRule;
use App\Models\Student;
use App\Services\WechatBindingService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(private readonly WechatBindingService $binding) {}

    /**
     * 学生端入口
     * - 未绑定 -> 跳到绑名页
     * - 已绑定 -> 跳到签到页
     */
    public function entry(Request $request)
    {
        $student = $this->binding->currentStudent();
        if (!$student) {
            return redirect()->route('student.bind');
        }
        return redirect()->route('student.checkin');
    }

    /**
     * 显示绑名页 (GET)
     */
    public function showBind(Request $request)
    {
        $current = $this->binding->currentStudent();
        if ($current) {
            return redirect()->route('student.checkin');
        }

        $students = Student::where('status', 'active')
            ->whereNull('openid')
            ->orderBy('class_name')->orderBy('dormitory')->orderBy('student_no')
            ->get(['id', 'name', 'class_name', 'dormitory', 'student_no']);

        return view('student.bind', [
            'students' => $students,
            'openid'   => $this->binding->currentOpenId(),
        ]);
    }

    /**
     * 提交绑名 (POST)
     */
    public function doBind(Request $request)
    {
        $data = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
        ]);

        $result = $this->binding->bind((int) $data['student_id']);
        if (!$result['ok']) {
            return back()->withErrors(['student_id' => $result['message']])->withInput();
        }
        return redirect()->route('student.checkin')->with('success', '身份已确认');
    }
}
