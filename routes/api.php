<?php

use App\Http\Controllers\ActionsController;
use App\Http\Controllers\CardsController;
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

Route::middleware(['api-auth', 'check-admin'])->group(function(){
    Route::put('/newCard', [CardsController::class, 'newCard']);
    Route::put('/newCollection', [CardsController::class, 'newCollection']);
});

Route::get('/listCards', [CardsController::class, 'listCards']);

Route::get('/listSales', [CardsController::class, 'listSales']);

Route::put('/newSale', [CardsController::class, 'newSale'])->middleware(['api-auth', 'check-other']);

Route::post('/login', [ActionsController::class, 'login']);

Route::put('/newUser', [ActionsController::class, 'newUser']);

Route::put('/asignCards', [CardsController::class, 'asignCards'])->middleware(['api-auth', 'check-admin']);;

Route::get('/recoverypass', [ActionsController::class, 'recoverypass']);

