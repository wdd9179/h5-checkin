<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CheckInController;
use App\Http\Controllers\Admin\CheckInRuleController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\StudentController;
use App\Http\Controllers\Admin\StudentImportController;
use App\Http\Controllers\Student\CheckInController as StudentCheckInController;
use App\Http\Controllers\Student\HomeController as StudentHomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 公共 / 学生端 (无登录要求)
|--------------------------------------------------------------------------
*/
Route::middleware('mock.wechat')->group(function () {
    Route::get('/', [StudentHomeController::class, 'entry'])->name('student.home');

    Route::get('/bind', [StudentHomeController::class, 'showBind'])->name('student.bind');
    Route::post('/bind', [StudentHomeController::class, 'doBind'])->name('student.bind.submit');

    Route::get('/checkin', [StudentCheckInController::class, 'show'])->name('student.checkin');
    Route::post('/checkin', [StudentCheckInController::class, 'submit'])->name('student.checkin.submit');

    Route::get('/my/history', [StudentCheckInController::class, 'history'])->name('student.history');
});

/*
|--------------------------------------------------------------------------
| 后台登录
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

/*
|--------------------------------------------------------------------------
| 后台 (需要 admin 登录)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // 学生管理
    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
    Route::post('/students', [StudentController::class, 'store'])->name('students.store');
    Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
    Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
    Route::post('/students/{student}/toggle', [StudentController::class, 'toggleStatus'])->name('students.toggle');
    Route::post('/students/{student}/unbind', [StudentController::class, 'unbind'])->name('students.unbind');

    // Excel 导入导出
    Route::get('/students/import', [StudentImportController::class, 'showForm'])->name('students.import');
    Route::post('/students/import', [StudentImportController::class, 'import'])->name('students.import.submit');
    Route::get('/students/template', [StudentImportController::class, 'template'])->name('students.template');
    Route::get('/students/export', [StudentImportController::class, 'export'])->name('students.export');

    // 签到规则
    Route::get('/rules', [CheckInRuleController::class, 'index'])->name('rules.index');
    Route::get('/rules/create', [CheckInRuleController::class, 'create'])->name('rules.create');
    Route::post('/rules', [CheckInRuleController::class, 'store'])->name('rules.store');
    Route::get('/rules/{rule}/edit', [CheckInRuleController::class, 'edit'])->name('rules.edit');
    Route::put('/rules/{rule}', [CheckInRuleController::class, 'update'])->name('rules.update');
    Route::delete('/rules/{rule}', [CheckInRuleController::class, 'destroy'])->name('rules.destroy');
    Route::get('/rules/share', [CheckInRuleController::class, 'shareLink'])->name('rules.share');

    // 签到详情 / 未签到 / 历史 / 导出
    Route::get('/checkins', [CheckInController::class, 'detail'])->name('checkins.detail');
    Route::get('/checkins/absent', [CheckInController::class, 'absent'])->name('checkins.absent');
    Route::get('/checkins/history', [CheckInController::class, 'history'])->name('checkins.history');
    Route::get('/checkins/export', [CheckInController::class, 'export'])->name('checkins.export');
});
