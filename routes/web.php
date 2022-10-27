<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MyController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('redirectgoogle', [MyController::class, 'redirectToGoogle']);
Route::get('testcallback', [MyController::class, 'handleGoogleCallback']);
Route::get('dashboard', [MyController::class, 'view']);

Route::match(['GET','POST'],'analytics-refresh',[MyController::class, 'refreshData'])->name('analytics.refresh');
Route::post('analytics/account/save',[MyController::class, 'handleGoogleCallback'])->name('save.analytics.account');

Route::post('analytics-data',[MyController::class, 'fetchAnalyticsData'])->name('analytics.data');
