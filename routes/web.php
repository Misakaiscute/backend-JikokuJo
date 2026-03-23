<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/reverb-tester', function () {
    return view('reverb-tester');
});
