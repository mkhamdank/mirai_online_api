<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/login', 'App\Http\Controllers\LoginController@store');

// CONTROL VENDOR
Route::middleware('auth:sanctum')->get('/fetch/plan_delivery', 'App\Http\Controllers\MasterController@fetchPlanDelivery');
Route::middleware('auth:sanctum')->get('/fetch/data_qa', 'App\Http\Controllers\MasterController@fetchDataQA');
Route::middleware('auth:sanctum')->post('/update/sync', 'App\Http\Controllers\MasterController@updateTableSync');
Route::middleware('auth:sanctum')->get('/generate_stock_policy', 'App\Http\Controllers\MasterController@generateStockPolicy');
Route::middleware('auth:sanctum')->get('/get_data_molding', 'App\Http\Controllers\MasterController@getAuditMolding');
Route::middleware('auth:sanctum')->get('/post_data_molding', 'App\Http\Controllers\MasterController@postAuditMolding');

//WPOS
Route::middleware('auth:sanctum')->get('/get_wpos', 'App\Http\Controllers\MasterController@getWPOS');

//EQ
Route::middleware('auth:sanctum')->get('/fetch/sync_equipment_delivery', 'App\Http\Controllers\MasterController@fetchEQDelivery');
Route::middleware('auth:sanctum')->post('/insert/sync_equipment_delivery', 'App\Http\Controllers\MasterController@insertEQDelivery');

Route::middleware('auth:sanctum')->post('/input/qr_code', 'App\Http\Controllers\MasterController@inputQrCode');
Route::middleware('auth:sanctum')->get('/fetch/driver_log', 'App\Http\Controllers\MasterController@fetchDriverLog');