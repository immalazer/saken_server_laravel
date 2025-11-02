<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SongController;
use App\Http\Controllers\DeviceController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/songs', [SongController::class, 'index']);
Route::get('/songs/{filename}', [SongController::class, 'show']);
Route::get('/play/{filename}', [SongController::class, 'play']);
Route::get('/art/{filename}', [SongController::class, 'albumArt']);
Route::post('/songs', [SongController::class, 'store']);
Route::post('/songs/{filename}', [SongController::class, 'update']);
Route::delete('/songs/{filename}', [SongController::class, 'destroy']);
Route::post('/devices', [DeviceController::class, 'register']);
Route::get('/devices', [DeviceController::class, 'get']);
Route::post('/devices/{deviceId}', [DeviceController::class, 'invoke']);