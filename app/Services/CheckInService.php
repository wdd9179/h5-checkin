<?php

namespace App\Services;

use App\Models\CheckIn;
use App\Models\CheckInRule;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckInService
{
    public function __construct(private readonly Request $request) {}

    /**
     * 学生尝试签到
     *
     * 返回:
     *   ['ok' => true,  'check_in' => CheckIn]                       成功
     *   ['ok' => false, 'reason' => 'already'|'not_started'|'late_closed'|'no_rule'|'inactive', 'message' => string]
     */
    public function checkIn(Student $student, ?Carbon $now = null): array
    {
        if (!$student->isActive()) {
            return ['ok' => false, 'reason' => 'inactive', 'message' => '账号已停用，请联系老师'];
        }

        $now ??= now();
        $date = $now->toDateString();
        $rule = CheckInRule::forDate($date);

        if (!$rule) {
            return ['ok' => false, 'reason' => 'no_rule', 'message' => '今日尚未开启签到'];
        }

        $phase = $rule->phaseAt($now);

        if ($phase === CheckInRule::PHASE_NOT_STARTED) {
            return ['ok' => false, 'reason' => 'not_started', 'message' => '签到尚未开始', 'rule' => $rule];
        }
        if ($phase === CheckInRule::PHASE_CLOSED) {
            return ['ok' => false, 'reason' => 'late_closed', 'message' => '签到已结束，如有特殊情况请联系老师', 'rule' => $rule];
        }

        // 唯一约束保护：upsert
        $status = $rule->statusForTime($now);

        try {
            $checkIn = DB::transaction(function () use ($student, $rule, $now, $date, $status) {
                $existing = CheckIn::where('student_id', $student->id)
                    ->whereDate('check_date', $date)
                    ->lockForUpdate()
                    ->first();
                if ($existing) {
                    $existing->fill([
                        'check_time' => $now,
                        'status'     => $status,
                        'ip'         => $this->request->ip(),
                        'user_agent' => substr((string) $this->request->userAgent(), 0, 500),
                    ])->save();
                    return $existing;
                }
                return CheckIn::create([
                    'student_id' => $student->id,
                    'check_date' => $date,
                    'check_time' => $now,
                    'status'     => $status,
                    'ip'         => $this->request->ip(),
                    'user_agent' => substr((string) $this->request->userAgent(), 0, 500),
                ]);
            });
        } catch (\Throwable $e) {
            return ['ok' => false, 'reason' => 'db_error', 'message' => '签到失败，请重试'];
        }

        return ['ok' => true, 'check_in' => $checkIn, 'rule' => $rule, 'phase' => $phase];
    }

    /**
     * 给定日期的签到统计
     */
    public function dailyStats(string $date): array
    {
        $total = Student::where('status', 'active')->count();
        $signed = CheckIn::whereDate('check_date', $date)->get();
        $normal = $signed->where('status', CheckIn::STATUS_NORMAL)->count();
        $late = $signed->where('status', CheckIn::STATUS_LATE)->count();
        $absent = $total - $signed->count();

        return [
            'date'   => $date,
            'total'  => $total,
            'signed' => $signed->count(),
            'normal' => $normal,
            'late'   => $late,
            'absent' => $absent,
            'rate'   => $total > 0 ? round($signed->count() / $total * 100, 1) : 0.0,
        ];
    }

    /**
     * 给定日期的全部明细
     */
    public function dailyDetail(string $date, ?string $filter = null)
    {
        $students = Student::where('status', 'active')
            ->orderBy('class_name')
            ->orderBy('dormitory')
            ->orderBy('student_no')
            ->get();

        $checkIns = CheckIn::whereDate('check_date', $date)
            ->get()
            ->keyBy('student_id');

        $rows = $students->map(function (Student $s) use ($checkIns) {
            $ci = $checkIns->get($s->id);
            return (object) [
                'student'    => $s,
                'check_in'   => $ci,
                'status'     => $ci?->status,
                'check_time' => $ci?->check_time,
            ];
        });

        return match ($filter) {
            'normal'  => $rows->where('status', CheckIn::STATUS_NORMAL)->values(),
            'late'    => $rows->where('status', CheckIn::STATUS_LATE)->values(),
            'absent'  => $rows->whereNull('status')->values(),
            default   => $rows->values(),
        };
    }
}
