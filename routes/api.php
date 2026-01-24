<?php

use App\Http\Controllers\DeviceController;
use App\Http\Controllers\SongController;
use App\Http\Middleware\LocalhostOrDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware([LocalhostOrDevice::class])->group(function () {
    Route::post('/songs', [SongController::class, 'store']);
    Route::put('/songs/{filename}', [SongController::class, 'update']);
    Route::delete('/songs/{filename}', [SongController::class, 'destroy']);
    Route::get('/devices', [DeviceController::class, 'get']);
    Route::post('/devices/{deviceId}', [DeviceController::class, 'invoke']);
});

Route::withoutMiddleware([LocalhostOrDevice::class])->group(function () {
    Route::get('/songs', [SongController::class, 'index']);
    Route::get('/songs/{filename}', [SongController::class, 'show']);
    Route::get('/play/{filename}', [SongController::class, 'play']);
    Route::get('/art/{filename}', [SongController::class, 'albumArt']);
    Route::post('/devices', [DeviceController::class, 'register']);
    Route::get('/devices/permission/', [DeviceController::class, 'getPermission']);
    Route::post('/devices/{deviceKey}/permission', [DeviceController::class, 'setPermission']);
});
