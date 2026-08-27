<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\CheckInService;
use App\Services\WechatBindingService;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    public function __construct(
        private readonly WechatBindingService $binding,
        private readonly CheckInService $svc,
    ) {}

    public function show(Request $request)
    {
        $student = $this->binding->currentStudent();
        if (!$student) {
            return redirect()->route('student.bind');
        }

        $date = now()->toDateString();
        $rule = \App\Models\CheckInRule::forDate($date);
        $phase = $rule?->phaseAt() ?? \App\Models\CheckInRule::PHASE_CLOSED;
        $existing = $student->latestCheckInForDate($date);

        return view('student.checkin', [
            'student'  => $student,
            'rule'     => $rule,
            'phase'    => $phase,
            'existing' => $existing,
            'date'     => $date,
        ]);
    }

    public function submit(Request $request)
    {
        $student = $this->binding->currentStudent();
        if (!$student) {
            return redirect()->route('student.bind');
        }

        $result = $this->svc->checkIn($student);

        if (!$result['ok']) {
            $request->session()->flash('error', $result['message']);
            return back();
        }

        return view('student.success', [
            'student'  => $student,
            'checkIn'  => $result['check_in'],
            'rule'     => $result['rule'] ?? null,
            'phase'    => $result['phase'] ?? null,
        ]);
    }

    public function history(Request $request)
    {
        $student = $this->binding->currentStudent();
        if (!$student) {
            return redirect()->route('student.bind');
        }
        $records = $student->checkIns()
            ->whereDate('check_date', '>=', now()->subDays(30)->toDateString())
            ->orderByDesc('check_date')
            ->get();
        return view('student.history', compact('student', 'records'));
    }
}
