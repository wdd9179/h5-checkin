<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CheckInRule;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CheckInRuleController extends Controller
{
    public function index()
    {
        $rules = CheckInRule::orderByDesc('check_date')->paginate(20);
        return view('admin.rules.index', compact('rules'));
    }

    public function create(Request $request)
    {
        $date = $request->query('date') ?: now()->toDateString();
        $rule = CheckInRule::forDate($date);
        return view('admin.rules.create', [
            'date' => $date,
            'rule' => $rule,
            'defaults' => [
                'start_time'      => '21:30',
                'normal_end_time' => '22:00',
                'final_end_time'  => '22:30',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'check_date'      => ['required', 'date'],
            'start_time'      => ['required', 'date_format:H:i'],
            'normal_end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'final_end_time'  => ['required', 'date_format:H:i', 'after:normal_end_time'],
            'status'          => ['required', 'in:open,closed'],
            'note'            => ['nullable', 'string', 'max:255'],
        ]);

        $rule = CheckInRule::updateOrCreate(
            ['check_date' => $data['check_date']],
            $data
        );

        return redirect()->route('admin.rules.index')->with('success', "已保存 {$rule->check_date->format('Y-m-d')} 的签到规则");
    }

    public function edit(CheckInRule $rule)
    {
        return view('admin.rules.create', [
            'date'     => $rule->check_date->toDateString(),
            'rule'     => $rule,
            'defaults' => [],
        ]);
    }

    public function update(Request $request, CheckInRule $rule)
    {
        $data = $request->validate([
            'start_time'      => ['required', 'date_format:H:i'],
            'normal_end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'final_end_time'  => ['required', 'date_format:H:i', 'after:normal_end_time'],
            'status'          => ['required', 'in:open,closed'],
            'note'            => ['nullable', 'string', 'max:255'],
        ]);
        $rule->update($data);
        return redirect()->route('admin.rules.index')->with('success', '已更新');
    }

    public function destroy(CheckInRule $rule)
    {
        $rule->delete();
        return redirect()->route('admin.rules.index')->with('success', '已删除规则');
    }

    public function shareLink(Request $request)
    {
        $date = $request->query('date') ?: now()->toDateString();
        $url = route('student.home', ['date' => $date]);
        return view('admin.rules.share', [
            'date' => $date,
            'url'  => $url,
        ]);
    }
}
