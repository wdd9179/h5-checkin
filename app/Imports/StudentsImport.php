<?php

namespace App\Imports;

use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToCollection, WithHeadingRow
{
    public int $successCount = 0;
    public int $failureCount = 0;
    public array $failures = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $idx => $row) {
            $name = trim((string)($row['姓名'] ?? $row['name'] ?? ''));
            if ($name === '') {
                continue; // 空行跳过
            }
            try {
                DB::transaction(function () use ($row, $name) {
                    Student::updateOrCreate(
                        [
                            'name'       => $name,
                            'student_no' => trim((string)($row['学号'] ?? $row['student_no'] ?? '')) ?: null,
                        ],
                        [
                            'class_name' => trim((string)($row['班级'] ?? $row['class_name'] ?? '')) ?: null,
                            'dormitory'  => trim((string)($row['宿舍'] ?? $row['dormitory'] ?? '')) ?: null,
                            'phone'      => trim((string)($row['手机号'] ?? $row['phone'] ?? '')) ?: null,
                            'status'     => 'active',
                        ]
                    );
                });
                $this->successCount++;
            } catch (\Throwable $e) {
                $this->failureCount++;
                $this->failures[] = "第 " . ($idx + 2) . " 行: {$name} - " . $e->getMessage();
            }
        }
    }
}
