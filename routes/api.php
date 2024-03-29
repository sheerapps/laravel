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

Route::get('/data-by-date/{date}', [ApiController::class, 'getDataByDate']);
Route::get('/main-by-date/{date}', [ApiController::class, 'getMainByDate']);
Route::get('/data-by-history', [ApiController::class, 'getDataBySearch']);
Route::get('/data-by-dictionary', [ApiController::class, 'getDicByData']);
Route::get('/save-by-date/{date}', [ApiController::class, 'saveData']);
Route::get('/data-draw-date', [ApiController::class, 'getDrawdateData']);
Route::get('/all-book-data', [ApiController::class, 'getBookAll']);
//tsheer
Route::get('/tmain-by-date/{date}', [ApiController::class, 'getTMainByDate']);
