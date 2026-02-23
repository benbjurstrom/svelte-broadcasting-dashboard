<?php

use App\Http\Controllers\BroadcastingDemoController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [BroadcastingDemoController::class, 'login'])->name('login');

Route::middleware('auth')->group(function () {
    Route::get('/', [BroadcastingDemoController::class, 'index'])
        ->name('broadcasting.index');
    Route::post('/switch-user', [BroadcastingDemoController::class, 'switchUser'])
        ->name('broadcasting.switch-user');
    Route::post('/public-event', [BroadcastingDemoController::class, 'triggerPublicEvent'])
        ->name('broadcasting.public-event');
    Route::post('/private-event', [BroadcastingDemoController::class, 'triggerPrivateEvent'])
        ->name('broadcasting.private-event');
    Route::post('/presence-event', [BroadcastingDemoController::class, 'triggerPresenceEvent'])
        ->name('broadcasting.presence-event');
    Route::post('/model-event', [BroadcastingDemoController::class, 'triggerModelEvent'])
        ->name('broadcasting.model-event');
    Route::post('/notification', [BroadcastingDemoController::class, 'triggerNotification'])
        ->name('broadcasting.notification');
});
