<?php

use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ScheduleController::class, 'index'])->name('schedules.index');
Route::post('/schedules', [ScheduleController::class, 'store'])->name('schedules.store');
Route::put('/schedules/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update');
Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy'])
    ->middleware('throttle:10,1') // tối đa 10 lần thử mật khẩu / phút
    ->name('schedules.destroy');
