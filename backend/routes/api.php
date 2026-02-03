<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TechStackController;

    Route::post('/contact', [ContactController::class, 'send']);
    Route::post('/login', [AuthController::class, 'login']);


    Route::apiResource('projects', ProjectController::class);
    Route::apiResource('projects.challenges', ChallengeController::class)->scoped();
    Route::apiResource('landingpages', LandingPageController::class);
    Route::apiResource('techstacks', TechStackController::class);
