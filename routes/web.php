<?php

use App\Http\Controllers\BroadcastingDemoController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});

Route::get('/login', fn () => redirect('/broadcasting/login'))->name('login');

Route::get('/broadcasting/login', [BroadcastingDemoController::class, 'login'])
    ->name('broadcasting.login');

Route::middleware('auth')->group(function () {
    Route::get('/broadcasting', [BroadcastingDemoController::class, 'index'])
        ->name('broadcasting.index');
    Route::post('/broadcasting/public-event', [BroadcastingDemoController::class, 'triggerPublicEvent'])
        ->name('broadcasting.public-event');
    Route::post('/broadcasting/private-event', [BroadcastingDemoController::class, 'triggerPrivateEvent'])
        ->name('broadcasting.private-event');
    Route::post('/broadcasting/presence-event', [BroadcastingDemoController::class, 'triggerPresenceEvent'])
        ->name('broadcasting.presence-event');
    Route::post('/broadcasting/model-event', [BroadcastingDemoController::class, 'triggerModelEvent'])
        ->name('broadcasting.model-event');
    Route::post('/broadcasting/notification', [BroadcastingDemoController::class, 'triggerNotification'])
        ->name('broadcasting.notification');
});
