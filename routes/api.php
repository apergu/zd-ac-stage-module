<?php

use App\Http\Controllers\ActiveCampaign\Contact\OnCreateController as AcContactOnCreateController;
use App\Http\Controllers\ActiveCampaign\Contact\TagController;
use App\Http\Controllers\Privy\CreateDealController;
use App\Http\Controllers\Privy\UpdateDealController;
use App\Http\Controllers\Privy\Lead\FreetrialController;
use App\Http\Controllers\Zendesk\Deal\OnChangeController as ZdDealOnChangeController;
use App\Http\Controllers\Zendesk\Deal\OnCreateController as ZdDealOnCreateController;
use App\Http\Controllers\Zendesk\Lead\OnChangeController as ZdLeadOnChangeController;
use App\Http\Controllers\Zendesk\Lead\OnCreateController as ZdLeadOnCreateController;
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
Route::group(['prefix' => 'v1'], function () {
  Route::group(['prefix' => 'zendesk', 'as' => 'zendesk.'], function () {
    Route::post('lead/on-create', [ZdLeadOnCreateController::class, 'index'])->middleware('basicAuth');
    Route::put('lead/on-change', [ZdLeadOnChangeController::class, 'index'])->middleware('basicAuth');

    Route::post('deal/on-create', [ZdDealOnCreateController::class, 'index'])->middleware('basicAuth');
    Route::put('deal/on-change', [ZdDealOnChangeController::class, 'index'])->middleware('basicAuth');
  });

  Route::group(['prefix' => 'activecampaign', 'as' => 'activecampaign.'], function () {
    Route::post('contact/on-create', [AcContactOnCreateController::class, 'index'])->middleware('apiKeyAuth');
    Route::post('contact/tag', [TagController::class, 'index'])->middleware('apiKeyAuth');
  });

  Route::group(['prefix' => 'privy', 'as' => 'privy.'], function () {
    Route::post('zendesk/deal', [CreateDealController::class, 'index']);
    Route::put('zendesk/deal', [UpdateDealController::class, 'index']);
  });

  Route::group(['prefix' => 'privy', 'as' => 'privy.'], function () {
    Route::post('zendesk/lead', [FreetrialController::class, 'index']);
    Route::put('zendesk/lead', [FreetrialController::class, 'index']);
  });
});
