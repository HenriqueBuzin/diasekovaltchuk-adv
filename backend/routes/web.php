<?php

use App\Http\Controllers\DocsController;
use App\Http\Controllers\FrontendController;
use Illuminate\Support\Facades\Route;

Route::get('/docs', [DocsController::class, 'ui']);
Route::get('/openapi.json', [DocsController::class, 'specification']);
Route::get('/{path?}', FrontendController::class)->where('path', '^(?!api(?:/|$)).*');
