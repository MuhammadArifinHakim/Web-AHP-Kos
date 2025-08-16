<?php

use App\Http\Controllers\Admin\BoardingHouseController;
use App\Http\Controllers\Admin\CriteriaController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\QuestionnaireController;
use App\Http\Controllers\AhpController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/select-campus', [HomeController::class, 'selectCampus'])->name('select.campus');

// Authentication routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// AHP Processing routes
Route::get('/pairwise', [AhpController::class, 'showPairwise'])->name('pairwise');
Route::post('/pairwise', [AhpController::class, 'processPairwise'])->name('process.pairwise');
Route::post('/process-system', [AhpController::class, 'processSystem'])->name('process.system');

// Admin routes
Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('criteria', CriteriaController::class);
    Route::resource('boarding-houses', BoardingHouseController::class);
    Route::resource('questionnaire', QuestionnaireController::class)->except(['show', 'edit', 'update']);
});
Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// routes/web.php (temporary)
Route::get('/debug/ahp/{campusId}', function($campusId){
    $svc = new \App\Services\AhpService();
    // gunakan user id yang sesuai (atau auth()->id())
    return response()->json($svc->runFullAhpForCampus(1, (int)$campusId));
});

