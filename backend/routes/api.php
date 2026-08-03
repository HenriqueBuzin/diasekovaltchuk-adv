<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\SiteConfigController;
use Illuminate\Support\Facades\Route;

Route::get('/site-config', SiteConfigController::class);
Route::post('/contact', ContactController::class);
Route::get('/contact', static fn () => abort(404));
Route::fallback(static fn () => response()->json(['message' => 'Not Found'], 404));
