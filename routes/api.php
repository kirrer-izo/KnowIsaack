<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactController;

Route::middleware('api')->group(function () {
    Route::post('/contact', [ContactController::class, 'send']);
});