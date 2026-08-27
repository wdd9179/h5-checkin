<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CheckInsExport;
use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\CheckInRule;
use App\Models\Student;
use App\Services\CheckInService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CheckInController extends Controller
{
    public function __construct(private readonly CheckInService $svc) {}

    public function detail(Request $request)
    {
        $date = $this->resolveDate($request);
        $filter = $request->query('filter', 'all');
        if (!in_array($filter, ['all', 'normal', 'late', 'absent'], true)) {
            $filter = 'all';
        }

        $rows = $this->svc->dailyDetail($date, $filter);
        $stats = $this->svc->dailyStats($date);
        $rule = CheckInRule::forDate($date);

        return view('admin.checkins.detail', [
            'date'   => $date,
            'filter' => $filter,
            'rows'   => $rows,
            'stats'  => $stats,
            'rule'   => $rule,
        ]);
    }

    public function absent(Request $request)
    {
        $date = $this->resolveDate($request);
        $rows = $this->svc->dailyDetail($date, 'absent');
        $rule = CheckInRule::forDate($date);

        return view('admin.checkins.absent', [
            'date'  => $date,
            'rows'  => $rows,
            'rule'  => $rule,
        ]);
    }

    public function history(Request $request)
    {
        $studentId = (int) $request->query('student_id', 0);
        $days = (int) $request->query('days', 14);
        if (!in_array($days, [7, 14, 30, 60], true)) $days = 14;

        if ($studentId) {
            $student = Student::find($studentId);
            if (!$student) {
                return back()->with('error', '学生不存在');
            }
            $records = CheckIn::where('student_id', $studentId)
                ->whereDate('check_date', '>=', now()->subDays($days)->toDateString())
                ->orderByDesc('check_date')
                ->get();
            $stats = $this->computeStudentStats($student, $days);
            return view('admin.checkins.history_student', compact('student', 'records', 'days', 'stats'));
        }

        // 总览：所有学生 + 最近 N 天
        $students = Student::where('status', 'active')
            ->orderBy('class_name')->orderBy('dormitory')
            ->get();
        $sinceDate = now()->subDays($days)->toDateString();
        $checkIns = CheckIn::whereDate('check_date', '>=', $sinceDate)->get()
            ->groupBy(fn ($c) => $c->student_id . '|' . $c->check_date->toDateString());

        $stats = $students->map(function (Student $s) use ($checkIns, $sinceDate) {
            $normal = 0; $late = 0; $absent = 0;
            $cursor = Carbon::parse($sinceDate);
            $end = now();
            while ($cursor->lte($end)) {
                $key = $s->id . '|' . $cursor->toDateString();
                $ci = $checkIns->get($key)?->first();
                if (!$ci) $absent++;
                elseif ($ci->status === CheckIn::STATUS_NORMAL) $normal++;
                elseif ($ci->status === CheckIn::STATUS_LATE) $late++;
                $cursor->addDay();
            }
            $total = $normal + $late + $absent;
            $rate = $total > 0 ? round(($normal + $late) / $total * 100, 1) : 0.0;
            return (object) compact('normal', 'late', 'absent', 'total', 'rate');
        });

        return view('admin.checkins.history_overview', [
            'students' => $students,
            'stats'    => $stats,
            'days'     => $days,
            'sinceDate'=> $sinceDate,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $date = $this->resolveDate($request);
        $rows = $this->svc->dailyDetail($date, 'all');
        $filename = "checkin_{$date}.xlsx";
        return Excel::download(new CheckInsExport($rows, $date), $filename, ExcelType::XLSX);
    }

    private function resolveDate(Request $request): string
    {
        $date = $request->query('date') ?: now()->toDateString();
        try {
            return Carbon::parse($date)->toDateString();
        } catch (\Throwable $e) {
            return now()->toDateString();
        }
    }

    private function computeStudentStats(Student $s, int $days): array
    {
        $since = now()->subDays($days)->toDateString();
        $rows = CheckIn::where('student_id', $s->id)
            ->whereDate('check_date', '>=', $since)->get();
        $normal = $rows->where('status', CheckIn::STATUS_NORMAL)->count();
        $late = $rows->where('status', CheckIn::STATUS_LATE)->count();
        $signed = $normal + $late;
        $total = $days;
        $absent = $total - $signed;
        $rate = $total > 0 ? round($signed / $total * 100, 1) : 0.0;
        return compact('normal', 'late', 'absent', 'total', 'rate');
    }
}
