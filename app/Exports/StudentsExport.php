<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class StudentsExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize
{
    public function __construct(private readonly Collection $students) {}

    public function collection(): Collection
    {
        return $this->students;
    }

    public function headings(): array
    {
        return ['姓名', '学号', '班级', '宿舍', '手机号', '状态', '微信绑定'];
    }

    public function map($s): array
    {
        return [
            $s->name,
            $s->student_no,
            $s->class_name,
            $s->dormitory,
            $s->phone,
            $s->status === 'active' ? '正常' : '已禁用',
            $s->openid ? '已绑定' : '未绑定',
        ];
    }

    public function title(): string
    {
        return '学生名单';
    }
}
