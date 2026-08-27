<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckInRule extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    public const PHASE_NOT_STARTED = 'not_started';
    public const PHASE_NORMAL = 'normal';
    public const PHASE_LATE = 'late';
    public const PHASE_CLOSED = 'closed';

    protected $fillable = [
        'check_date',
        'start_time',
        'normal_end_time',
        'final_end_time',
        'status',
        'note',
    ];

    protected $casts = [
        'check_date' => 'date',
    ];

    /**
     * 取得今日（默认）或指定日期的签到规则
     */
    public static function forDate(?string $date = null): ?self
    {
        $date ??= now()->toDateString();
        return static::whereDate('check_date', $date)->first();
    }

    /**
     * 判断给定时刻处于哪个阶段
     */
    public function phaseAt(?Carbon $now = null): string
    {
        $now ??= now();
        // 必须用同一天
        if ($now->toDateString() !== $this->check_date->toDateString()) {
            return $now->lt($this->check_date->startOfDay())
                ? self::PHASE_NOT_STARTED
                : self::PHASE_CLOSED;
        }

        if ($this->status === self::STATUS_CLOSED) {
            return self::PHASE_CLOSED;
        }

        $nowSec = $now->hour * 3600 + $now->minute * 60 + $now->second;
        $startSec = $this->timeToSeconds($this->start_time);
        $normalEndSec = $this->timeToSeconds($this->normal_end_time);
        $finalEndSec = $this->timeToSeconds($this->final_end_time);

        if ($nowSec < $startSec) return self::PHASE_NOT_STARTED;
        if ($nowSec <= $normalEndSec) return self::PHASE_NORMAL;
        if ($nowSec <= $finalEndSec) return self::PHASE_LATE;
        return self::PHASE_CLOSED;
    }

    public function phaseLabel(string $phase): string
    {
        return match ($phase) {
            self::PHASE_NOT_STARTED => '尚未开始',
            self::PHASE_NORMAL => '正常签到',
            self::PHASE_LATE => '迟到签到',
            self::PHASE_CLOSED => '签到已结束',
            default => '未知',
        };
    }

    public function phaseColor(string $phase): string
    {
        return match ($phase) {
            self::PHASE_NOT_STARTED => 'gray',
            self::PHASE_NORMAL => 'green',
            self::PHASE_LATE => 'orange',
            self::PHASE_CLOSED => 'red',
            default => 'gray',
        };
    }

    /**
     * 判定一个签到时刻属于哪种状态
     */
    public function statusForTime(Carbon $time): string
    {
        $sec = $time->hour * 3600 + $time->minute * 60 + $time->second;
        $normalEndSec = $this->timeToSeconds($this->normal_end_time);
        return $sec <= $normalEndSec ? CheckIn::STATUS_NORMAL : CheckIn::STATUS_LATE;
    }

    private function timeToSeconds(string $time): int
    {
        $parts = explode(':', $time);
        return ((int)$parts[0]) * 3600 + ((int)($parts[1] ?? 0)) * 60 + (int)($parts[2] ?? 0);
    }
}
