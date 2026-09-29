<?php

use App\Http\Controllers\ImageController;
use App\Http\Controllers\MessageController;
use App\Http\Middleware\MarkMessagesAfterResponse;
use App\Http\Middleware\ParseHeaders;
use Illuminate\Support\Facades\Route;

Route::post('images/fetch', [ImageController::class, 'fetch']);

Route::get('messages', [MessageController::class, 'index'])
    ->middleware([
        ParseHeaders::class,
        MarkMessagesAfterResponse::class,
    ]);
Route::post('messages', [MessageController::class, 'store'])
    ->middleware([ParseHeaders::class]);
