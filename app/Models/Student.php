<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'student_no',
        'class_name',
        'dormitory',
        'phone',
        'openid',
        'status',
        'bound_at',
    ];

    protected $casts = [
        'bound_at' => 'datetime',
    ];

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class);
    }

    public function isBound(): bool
    {
        return !empty($this->openid);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function latestCheckInForDate(string $date): ?CheckIn
    {
        return $this->checkIns()->whereDate('check_date', $date)->first();
    }
}
