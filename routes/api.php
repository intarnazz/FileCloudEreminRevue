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

use App\Http\Controllers\UserController;
use App\Http\Controllers\FileController;



header('Accept: application/json');

Route::post('/registration', [UserController::class, 'register']);
Route::post('/authorization', [UserController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('/logout', [UserController::class, 'logout']);
    Route::post('/files', [FileController::class, 'add']);
    Route::patch('/files/{file_id}', [FileController::class, 'change']);
    Route::delete('/files/{file_id}', [FileController::class, 'delete']);
    Route::get('/files/{file_id}', [FileController::class, 'get']);
    Route::post('/files/{file_id}/accesses', [FileController::class, 'setAccess']);
    Route::post('/files/{file_id}/accesses', [FileController::class, 'delAccess']);
});


