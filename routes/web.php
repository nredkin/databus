<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get(env('MONITORING_URL', date('YmdHHi')), function () {
    return view('monitoring');
});
