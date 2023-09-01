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