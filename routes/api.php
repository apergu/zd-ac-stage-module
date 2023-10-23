<?php

use App\Http\Controllers\ActiveCampaign\DealController as AcDealController;
use App\Http\Controllers\Zendesk\DealController as ZdDealDealController;
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
});

Route::group(['prefix' => 'activecampaign', 'as' => 'activecampaign.'], function () {
  Route::resource('deal', AcDealController::class)->only(['index', 'store', 'update']);
});
