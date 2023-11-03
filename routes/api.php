<?php

use App\Http\Controllers\ActiveCampaign\Contact\OnCreateController as AcContactOnCreateController;
use App\Http\Controllers\Zendesk\Deal\OnChangeController as ZdDealOnChangeController;
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
    Route::post('lead/on-create', [ZdLeadOnCreateController::class, 'index']);
    Route::put('lead/on-change', [ZdLeadOnChangeController::class, 'index']);
    Route::put('deal/on-change', [ZdDealOnChangeController::class, 'index']);
  });

  Route::group(['prefix' => 'activecampaign', 'as' => 'activecampaign.'], function () {
    Route::post('contact/on-create', [AcContactOnCreateController::class, 'index']);
  });
});
