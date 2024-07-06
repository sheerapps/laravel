<?php

use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;

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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    //return $request->user();
});

Route::get('/tsave/{date}', [ApiController::class, 'saveLive']);

Route::get('/data-by-date/{date}', [ApiController::class, 'getDataByDate']);
Route::get('/main-by-date/{date}', [ApiController::class, 'getMainByDate']);
Route::get('/main-by-date-v110/{date}', [ApiController::class, 'getMainByDateV1_1_0']);
Route::get('/main-by-date-v120/{date}', [ApiController::class, 'getMainByDateV1_2_0']); // no use


Route::get('/data-by-history', [ApiController::class, 'getDataBySearch']);
Route::get('/data-by-history-v120', [ApiController::class, 'getDataByAdvanceSearch']);
Route::get('/data-by-dictionary', [ApiController::class, 'getDicByData']);
Route::get('/save-by-date/{date}', [ApiController::class, 'saveData']);
Route::get('/save-by-live', [ApiController::class, 'saveLive']);

Route::get('/data-draw-date', [ApiController::class, 'getDrawdateData']);
Route::get('/all-book-data', [ApiController::class, 'getBookAll']);
//tsheer
Route::get('/tmain-by-date/{date}', [ApiController::class, 'getTMainByDate']);
Route::get('/t', function() {
    $ch = curl_init(); 
    curl_setopt( $ch, CURLOPT_URL, "https://backend.4dnum.com/api/v1/result/2024-06-06");
    curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
    $res = curl_exec($ch);
    $all = json_decode($res);
    print_r($all);
});