<?php

use Illuminate\Support\Facades\Route;
use Pterodactyl\Http\Controllers\Auth;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| Endpoint: /auth
|
*/

Route::get('/auth/login/keycloak', [Auth\KeycloakController::class, 'redirectToKeycloak'])->name('auth.keycloak.redirect');
Route::get('/auth/login/keycloak/callback', [Auth\KeycloakController::class, 'handleKeycloakCallback'])->name('auth.keycloak.callback');

// Redirect all login requests to keycloak.
Route::get('/login', [Auth\KeycloakController::class, 'redirectToKeycloak'])->name('auth.login');

// Deactivate password reset routes by redirecting to keycloak.
Route::get('/password', [Auth\KeycloakController::class, 'redirectToKeycloak'])->name('auth.forgot-password');
Route::get('/password/reset/{token}', [Auth\KeycloakController::class, 'redirectToKeycloak'])->name('auth.reset');

// Remove the guest middleware and apply the authenticated middleware to this endpoint,
// so it cannot be used unless you're already logged in.
Route::post('/logout', [Auth\LoginController::class, 'logout'])
    ->withoutMiddleware('guest')
    ->middleware('auth')
    ->name('auth.logout');

// Catch any other combinations of routes and pass them off to keycloak.
Route::fallback([Auth\KeycloakController::class, 'index']);
