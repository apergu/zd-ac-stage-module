<?php

use App\Http\Controllers\ActiveCampaign\ContactController;
use App\Http\Controllers\ActiveCampaign\DealController as AcDealController;
use App\Http\Controllers\ActiveCampaign\SyncStageController as AcSyncStageController;
use App\Http\Controllers\Global\SyncStagesController;
use App\Http\Controllers\Global\TestPayloadController;
use App\Http\Controllers\Zendesk\DealController as ZdDealDealController;
use App\Http\Controllers\ZenDesk\NewDealController;
use App\Http\Controllers\Zendesk\SyncStageController as ZdSyncStageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(['prefix' => 'zendesk', 'as' => 'zendesk.'], function () {
  Route::resource('deal', ZdDealDealController::class)->only(['index', 'store', 'update']);
  Route::resource('deals/ac-contact', NewDealController::class)->only(['index', 'store', 'update']);
  Route::get('stages/sync', [ZdSyncStageController::class, 'index'])->name('stages-sync');
});

Route::group(['prefix' => 'activecampaign', 'as' => 'activecampaign.'], function () {
  Route::resource('contact', ContactController::class)->only(['index', 'store', 'update']);
  Route::resource('deal', AcDealController::class)->only(['index', 'store', 'update']);
  Route::get('stages/sync', [AcSyncStageController::class, 'index'])->name('stages-sync');
});

Route::resource('test', TestPayloadController::class)->only(['store', 'update']);
Route::resource('synchronize', SyncStagesController::class)->only(['index']);
