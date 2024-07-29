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

Route::get('/tsave/{date}', [ApiController::class, 'saveDataV1_2_0']);

Route::get('/data-by-date/{date}', [ApiController::class, 'getDataByDate']);
Route::get('/main-by-date/{date}', [ApiController::class, 'getMainByDate']);
Route::get('/main-by-date-v130/{date}', [ApiController::class, 'getMainByDateV1_3_0']); //added GT/DL/MH results
Route::get('/main-by-date-v110/{date}', [ApiController::class, 'getMainByDateV1_2_0']); //update 1_1_0 to 1_2_0
Route::get('/main-by-date-v120/{date}', [ApiController::class, 'getMainByDateV1_2_1']); // new update app 7/7/2024 change results arrangement only
Route::get('/tmain-by-date/{date}', [ApiController::class, 'getTMainByDateV1_2_0']); //update getTMainByDate to 1_2_0 code

Route::get('/save-to-db/{date}', [ApiController::class, 'saveLiveDB']);
Route::get('/data-by-history', [ApiController::class, 'getDataBySearch']);
Route::get('/data-by-history-v120', [ApiController::class, 'getDataByAdvanceSearch']);
Route::get('/data-by-dictionary', [ApiController::class, 'getDicByData']);
Route::get('/save-by-date/{date}', [ApiController::class, 'saveData']);
Route::get('/save-by-live', [ApiController::class, 'saveLive']);
Route::get('/date-by-list', [ApiController::class, 'drawDrawList']);

Route::get('/data-draw-date', [ApiController::class, 'getDrawdateData']);
Route::get('/all-book-data', [ApiController::class, 'getBookAll']);
//tsheer

Route::get('/results', [ApiController::class, 'getDataPH']);
