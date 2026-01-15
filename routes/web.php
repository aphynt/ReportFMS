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
use App\Http\Controllers\InspeksiController;
use App\Http\Controllers\PlanController;
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

    //Inspeksi
    Route::get('/inspeksi/index', [InspeksiController::class, 'index'])->name('inspeksi.index');
    Route::get('/inspeksi/api', [InspeksiController::class, 'api'])->name('inspeksi.api');

    Route::get('/payload/ex/summary', [PayloadExcavatorController::class, 'summary'])->name('payload.ex.summary');
    Route::get('/payload/ex/summary/api', [PayloadExcavatorController::class, 'apiSummary'])->name('payload.ex.apiSummary');
    Route::get('/payload/ex/summary/excel', [PayloadExcavatorController::class, 'summaryExportExcel'])->name('payload.ex.summaryExcel');

    Route::get('/payload/ex/oneHundredandFifteen', [PayloadExcavatorController::class, 'oneHundredandFifteen'])->name('payload.ex.oneHundredandFifteen');
    Route::get('/payload/ex/oneHundredandFifteen/api', [PayloadExcavatorController::class, 'apiOneHundredandFifteen'])->name('payload.ex.apiOneHundredandFifteen');
    Route::get('/payload/ex/oneHundredandFifteen/excel', [PayloadExcavatorController::class, 'excelOneHundredandFifteen'])->name('payload.ex.excelOneHundredandFifteen');

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

    Route::get('/plan/ex', [PlanController::class, 'ex'])->name('plan.ex');
    Route::get('/plan/ex/api', [PlanController::class, 'api_ex'])->name('plan.ex.api');
    Route::post('/plan/ex/store', [PlanController::class, 'ex_store'])->name('plan.ex.store');
    Route::post('/plan/ex/update', [PlanController::class, 'ex_update'])->name('plan.ex.update');
    Route::post('/plan/ex/delete/{id}', [PlanController::class, 'ex_delete'])->name('plan.ex.delete');

});
