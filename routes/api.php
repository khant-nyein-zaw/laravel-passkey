<?php

use App\Http\Controllers\Api\AuthenticateUser;
use App\Http\Controllers\Api\PasskeyController;
use Illuminate\Support\Facades\Route;

Route::post('/tokens/create', AuthenticateUser::class);
Route::get('/passkeys/register', [PasskeyController::class, 'registerOptions'])->middleware('auth:sanctum');
Route::get('/passkeys/authenticate', [PasskeyController::class, 'authenticateOptions']);
