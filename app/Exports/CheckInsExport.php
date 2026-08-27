<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class CheckInsExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    public function __construct(private readonly Collection $rows, private readonly string $date) {}

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['姓名', '学号', '班级', '宿舍', '状态', '签到时间', 'IP'];
    }

    public function map($row): array
    {
        $s = $row->student;
        $ci = $row->check_in;
        $status = match (true) {
            !$ci => '未签到',
            $ci->status === 'normal' => '正常',
            $ci->status === 'late' => '迟到',
            default => '-',
        };
        return [
            $s->name,
            $s->student_no,
            $s->class_name,
            $s->dormitory,
            $status,
            $ci?->check_time?->format('H:i:s') ?: '-',
            $ci?->ip ?: '-',
        ];
    }

    public function title(): string
    {
        return $this->date;
    }
}
