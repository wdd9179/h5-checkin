<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CheckInRule;
use App\Services\CheckInService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly CheckInService $checkInService) {}

    public function index(Request $request)
    {
        $date = $request->query('date') ?: now()->toDateString();
        // 简单日期格式校验
        try {
            $date = Carbon::parse($date)->toDateString();
        } catch (\Throwable $e) {
            $date = now()->toDateString();
        }

        $stats = $this->checkInService->dailyStats($date);
        $rule  = CheckInRule::forDate($date);
        $phase = $rule?->phaseAt() ?? CheckInRule::PHASE_CLOSED;

        return view('admin.dashboard', [
            'date'   => $date,
            'stats'  => $stats,
            'rule'   => $rule,
            'phase'  => $phase,
        ]);
    }
}
