<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckIn extends Model
{
    use HasFactory;

    public const STATUS_NORMAL = 'normal';
    public const STATUS_LATE = 'late';

    protected $fillable = [
        'student_id',
        'check_date',
        'check_time',
        'status',
        'ip',
        'user_agent',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'check_time' => 'datetime',
        'check_date' => 'date',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_NORMAL => '正常',
            self::STATUS_LATE => '迟到',
            default => '未知',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_NORMAL => 'green',
            self::STATUS_LATE => 'orange',
            default => 'gray',
        };
    }
}
