<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KLKHBatuBaraController;
use App\Http\Controllers\KLKHDisposalController;
use App\Http\Controllers\KLKHHaulRoadController;
use App\Http\Controllers\KLKHLoadingPoint;
use App\Http\Controllers\KLKHLoadingPointController;
use App\Http\Controllers\KLKHLumpurController;
use App\Http\Controllers\KLKHOGSController;
use App\Http\Controllers\KLKHSimpangEmpatController;
use App\Http\Controllers\PayloadExcavatorController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard.index');
});

Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login/post', [AuthController::class, 'login_post'])->name('login.post');

Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

Route::group(['middleware' => ['auth']], function(){

    Route::get('/dashboard/index', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('/dashboard/api', [DashboardController::class, 'api'])->name('dashboard.api');

    Route::get('/payload/exa', [PayloadExcavatorController::class, 'index'])->name('payload.ex.index');
    Route::get('/payload/api', [PayloadExcavatorController::class, 'api'])->name('payload.ex.api');
    Route::get('/payload/excel', [PayloadExcavatorController::class, 'exportExcel'])->name('payload.ex.excel');

    Route::get('/klkh/loading-point', [KLKHLoadingPointController::class, 'index'])->name('loadingPoint.index');
    Route::get('/klkh/loading-point/api', [KLKHLoadingPointController::class, 'api'])->name('loadingPoint.api');
    Route::get('/klkh/loading-point/excel', [KLKHLoadingPointController::class, 'exportExcel'])->name('loadingPoint.excel');

    Route::get('/klkh/haul-road', [KLKHHaulRoadController::class, 'index'])->name('haulRoad.index');
    Route::get('/klkh/haul-road/api', [KLKHHaulRoadController::class, 'api'])->name('haulRoad.api');
    Route::get('/klkh/haul-road/excel', [KLKHHaulRoadController::class, 'exportExcel'])->name('haulRoad.excel');

    Route::get('/klkh/disposal', [KLKHDisposalController::class, 'index'])->name('disposal.index');
    Route::get('/klkh/disposal/api', [KLKHDisposalController::class, 'api'])->name('disposal.api');
    Route::get('/klkh/disposal/excel', [KLKHDisposalController::class, 'exportExcel'])->name('disposal.excel');

    Route::get('/klkh/lumpur', [KLKHLumpurController::class, 'index'])->name('lumpur.index');
    Route::get('/klkh/lumpur/api', [KLKHLumpurController::class, 'api'])->name('lumpur.api');
    Route::get('/klkh/lumpur/excel', [KLKHLumpurController::class, 'exportExcel'])->name('lumpur.excel');

    Route::get('/klkh/ogs', [KLKHOGSController::class, 'index'])->name('ogs.index');
    Route::get('/klkh/ogs/api', [KLKHOGSController::class, 'api'])->name('ogs.api');
    Route::get('/klkh/ogs/excel', [KLKHOGSController::class, 'exportExcel'])->name('ogs.excel');

    Route::get('/klkh/batu-bara', [KLKHBatuBaraController::class, 'index'])->name('batuBara.index');
    Route::get('/klkh/batu-bara/api', [KLKHBatuBaraController::class, 'api'])->name('batuBara.api');
    Route::get('/klkh/batu-bara/excel', [KLKHBatuBaraController::class, 'exportExcel'])->name('batuBara.excel');

    Route::get('/klkh/simpang-empat', [KLKHSimpangEmpatController::class, 'index'])->name('simpangEmpat.index');
    Route::get('/klkh/simpang-empat/api', [KLKHSimpangEmpatController::class, 'api'])->name('simpangEmpat.api');
    Route::get('/klkh/simpang-empat/excel', [KLKHSimpangEmpatController::class, 'exportExcel'])->name('simpangEmpat.excel');

});
