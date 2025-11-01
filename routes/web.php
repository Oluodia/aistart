<?php

use App\Http\Controllers\AiImageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AiImageController::class, 'index'])->name('ai-images');
Route::post('/', [AiImageController::class, 'generate'])->name('ai-images.generate');
