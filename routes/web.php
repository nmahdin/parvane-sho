<?php

use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Panel\AuthController;
use App\Http\Controllers\Panel\ChallengeController as PanelChallengeController;
use App\Http\Controllers\Panel\ChallengeTaskController;
use App\Http\Controllers\Panel\DashboardController;
use App\Http\Controllers\Panel\ParticipantController as PanelParticipantController;
use App\Http\Controllers\Panel\SettingController;
use App\Http\Controllers\ParticipantDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| بخش عمومی سایت
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/challenges', [ChallengeController::class, 'index'])->name('challenges.index');
Route::get('/challenges/{challenge}', [ChallengeController::class, 'show'])->name('challenges.show');

/*
| داشبورد اختصاصی هر شرکت‌کننده — بدون نیاز به ورود، فقط با لینک یکتا
*/
Route::get('/p/{participant}', [ParticipantDashboardController::class, 'show'])->name('participant.show');
Route::post('/p/{participant}/tasks/{task}', [ParticipantDashboardController::class, 'toggle'])->name('participant.tasks.toggle');
Route::put('/p/{participant}/note', [ParticipantDashboardController::class, 'updateNote'])->name('participant.note');

/*
|--------------------------------------------------------------------------
| پنل مدیریت — ورود فقط با رمز عبور
|--------------------------------------------------------------------------
*/

Route::prefix('panel')->name('panel.')->group(function () {
    Route::middleware('panel.guest')->group(function () {
        Route::get('login', [AuthController::class, 'showLogin'])->name('login');
        Route::post('login', [AuthController::class, 'login'])->name('login.attempt');
    });

    Route::middleware('panel.auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // چالش‌ها
        Route::get('challenges', [PanelChallengeController::class, 'index'])->name('challenges.index');
        Route::get('challenges/create', [PanelChallengeController::class, 'create'])->name('challenges.create');
        Route::post('challenges', [PanelChallengeController::class, 'store'])->name('challenges.store');
        Route::get('challenges/{challenge}/edit', [PanelChallengeController::class, 'edit'])->name('challenges.edit');
        Route::put('challenges/{challenge}', [PanelChallengeController::class, 'update'])->name('challenges.update');
        Route::delete('challenges/{challenge}', [PanelChallengeController::class, 'destroy'])->name('challenges.destroy');
        Route::patch('challenges/{challenge}/publish', [PanelChallengeController::class, 'togglePublish'])->name('challenges.publish');
        Route::get('challenges/{challenge}', [PanelChallengeController::class, 'show'])->name('challenges.show');

        // کارهای هر روز چالش
        Route::post('challenges/{challenge}/tasks', [ChallengeTaskController::class, 'store'])->name('tasks.store');
        Route::post('challenges/{challenge}/tasks/bulk', [ChallengeTaskController::class, 'bulkStore'])->name('tasks.bulk');
        Route::put('challenges/{challenge}/tasks/{task}', [ChallengeTaskController::class, 'update'])->name('tasks.update');
        Route::delete('challenges/{challenge}/tasks/{task}', [ChallengeTaskController::class, 'destroy'])->name('tasks.destroy');
        Route::patch('challenges/{challenge}/tasks/{task}/move/{direction}', [ChallengeTaskController::class, 'move'])->name('tasks.move');

        // شرکت‌کننده‌ها
        Route::get('participants', [PanelParticipantController::class, 'index'])->name('participants.index');
        Route::post('challenges/{challenge}/participants', [PanelParticipantController::class, 'store'])->name('participants.store');
        Route::get('participants/{participant}', [PanelParticipantController::class, 'show'])->name('participants.show');
        Route::put('participants/{participant}', [PanelParticipantController::class, 'update'])->name('participants.update');
        Route::delete('participants/{participant}', [PanelParticipantController::class, 'destroy'])->name('participants.destroy');
        Route::patch('participants/{participant}/token', [PanelParticipantController::class, 'regenerateToken'])->name('participants.regenerate');
        Route::post('participants/{participant}/tasks/{task}', [PanelParticipantController::class, 'toggleTask'])->name('participants.tasks.toggle');

        // تنظیمات
        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
        Route::put('settings/password', [SettingController::class, 'updatePassword'])->name('settings.password');
    });
});
