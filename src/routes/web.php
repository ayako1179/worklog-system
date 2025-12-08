<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BreakTimeController;
use App\Http\Controllers\CorrectionController;
use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\Admin\CorrectionController as AdminCorrectionController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

Route::get('/admin/login', function () {
    return view('admin.auth.login');
})->name('admin.login');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('home');
    Route::post('/attendances/start', [AttendanceController::class, 'start'])->name('attendance.start');
    Route::post('/attendances/end', [AttendanceController::class, 'end'])->name('attendance.end');
    Route::post('/breaks/start', [BreakTimeController::class, 'start'])->name('break.start');
    Route::post('/breaks/end', [BreakTimeController::class, 'end'])->name('break.end');
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show'])->name('attendance.detail.show');
    Route::post('/attendance/detail/{id}', [AttendanceController::class, 'submit'])->name('attendance.detail.submit');
    Route::get('/stamp_correction_request/list', [CorrectionController::class, 'index'])->name('correction.index');
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'list'])->name('admin.attendance.list');
    Route::get('/admin/staff/list', [AdminUserController::class, 'index'])->name('admin.staff');
    Route::get('/admin/attendance/staff/{id}', [AdminAttendanceController::class, 'staffMonthlyList'])->name('admin.attendance.staff');
    Route::get('/admin/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('admin.attendance.show');
    Route::post('/admin/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('admin.attendance.update');
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminCorrectionController::class, 'show'])->name('admin.approve.show');
    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminCorrectionController::class, 'approve'])->name('admin.approve');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
