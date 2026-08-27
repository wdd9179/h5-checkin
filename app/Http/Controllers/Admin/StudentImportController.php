<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\StudentsImport;
use App\Models\Student;
use App\Exports\StudentsExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ExcelType;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentImportController extends Controller
{
    public function showForm()
    {
        return view('admin.students.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $importer = new StudentsImport();
        Excel::import($importer, $request->file('file'));

        return back()->with('success', sprintf(
            '导入完成：成功 %d 条，失败 %d 条%s',
            $importer->successCount,
            $importer->failureCount,
            $importer->failureCount > 0 ? '，请检查表头与数据格式' : ''
        ));
    }

    public function template()
    {
        return Excel::download(new StudentsExport(collect()), 'students_template.xlsx', ExcelType::XLSX);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $q = trim((string) $request->query('q', ''));
        $status = $request->query('status', 'all');

        $query = Student::query()->orderBy('class_name')->orderBy('dormitory');
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%$q%")
                  ->orWhere('student_no', 'like', "%$q%")
                  ->orWhere('dormitory', 'like', "%$q%");
            });
        }
        if (in_array($status, ['active', 'disabled'], true)) {
            $query->where('status', $status);
        }

        $filename = 'students_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new StudentsExport($query->get()), $filename, ExcelType::XLSX);
    }
}
