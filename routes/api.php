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

// CONTROL VENDOR EQ
Route::middleware('auth:sanctum')->get('/fetch/plan_delivery', 'App\Http\Controllers\MasterController@fetchPlanDelivery');
Route::middleware('auth:sanctum')->get('/fetch/data_qa', 'App\Http\Controllers\MasterController@fetchDataQA');
Route::middleware('auth:sanctum')->post('/update/sync', 'App\Http\Controllers\MasterController@updateTableSync');
Route::middleware('auth:sanctum')->get('/generate_stock_policy', 'App\Http\Controllers\MasterController@generateStockPolicy');
Route::middleware('auth:sanctum')->get('/get_data_molding', 'App\Http\Controllers\MasterController@getAuditMolding');
Route::middleware('auth:sanctum')->get('/post_data_molding', 'App\Http\Controllers\MasterController@postAuditMolding');

// CONTROL VENDOR MATERIAL
Route::middleware('auth:sanctum')->post('/insert/plan_delivery', 'App\Http\Controllers\MasterController@insertPlanDelivery');
Route::middleware('auth:sanctum')->get('/fetch/sync_plan_delivery', 'App\Http\Controllers\MasterController@getSyncPlanDelivery');
Route::middleware('auth:sanctum')->post('/insert/vendor_mail', 'App\Http\Controllers\MasterController@insertVendorMail');
Route::middleware('auth:sanctum')->post('/update/raw_material_control', 'App\Http\Controllers\MasterController@updateRawMaterialControl');

//WPOS
Route::middleware('auth:sanctum')->get('/get_wpos', 'App\Http\Controllers\MasterController@getWPOS');
Route::middleware('auth:sanctum')->post('/get_wpos_id', 'App\Http\Controllers\MasterController@getWPOSId');
Route::middleware('auth:sanctum')->post('/post_wpos_approval', 'App\Http\Controllers\MasterController@postWPOSApproval');

//EQ
Route::middleware('auth:sanctum')->get('/fetch/sync_equipment_delivery', 'App\Http\Controllers\MasterController@fetchEQDelivery');
Route::middleware('auth:sanctum')->post('/insert/sync_equipment_delivery', 'App\Http\Controllers\MasterController@insertEQDelivery');

Route::middleware('auth:sanctum')->post('/input/qr_code', 'App\Http\Controllers\MasterController@inputQrCode');
Route::middleware('auth:sanctum')->get('/fetch/driver_log', 'App\Http\Controllers\MasterController@fetchDriverLog');
Route::middleware('auth:sanctum')->get('/fetch/attendance', 'App\Http\Controllers\MasterController@getAttendance');

Route::middleware('auth:sanctum')->post('/delete/driver_task/{task_id}', 'App\Http\Controllers\MasterController@deleteDriverTask');

Route::middleware('auth:sanctum')->post('/input/driver_task', 'App\Http\Controllers\MasterController@inputDriverTask');

// FA
Route::middleware('auth:sanctum')->get('/fetch/sync_fixed_asset', 'App\Http\Controllers\MasterController@syncFixedAsset');
Route::middleware('auth:sanctum')->post('/insert/approval_fixed_asset', 'App\Http\Controllers\MasterController@insertFixedAsset');
Route::middleware('auth:sanctum')->post('/insert/fixed_asset', 'App\Http\Controllers\MasterController@AddFixedAsset');

Route::middleware('auth:sanctum')->get('/fetch/vendor_gift', 'App\Http\Controllers\MasterController@fetchVendorGift');
Route::middleware('auth:sanctum')->get('/fetch/vendor_holiday', 'App\Http\Controllers\MasterController@fetchVendorHoliday');