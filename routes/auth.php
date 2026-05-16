<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
|
| Endpoint: /auth
|
*/

Route::get('/auth/login/keycloak', 'KeycloakController@redirectToKeycloak')->name('auth.keycloak.redirect');
Route::get('/auth/login/keycloak/callback', 'KeycloakController@handleKeycloakCallback')->name('auth.keycloak.callback');

// Redirect all login requests to keycloak.
Route::get('/login', 'KeycloakController@redirectToKeycloak')->name('auth.login');

// Deactivate password reset routes by redirecting to keycloak.
Route::get('/password', 'KeycloakController@redirectToKeycloak')->name('auth.forgot-password');
Route::get('/password/reset/{token}', 'KeycloakController@redirectToKeycloak')->name('auth.reset');

// Remove the guest middleware and apply the authenticated middleware to this endpoint,
// so it cannot be used unless you're already logged in.
Route::post('/logout', 'LoginController@logout')
    ->withoutMiddleware('guest')
    ->middleware('auth')
    ->name('auth.logout');

Route::fallback('LoginController@index');
