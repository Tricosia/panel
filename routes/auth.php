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

Route::get('/login/keycloak', 'Pterodactyl\Http\Controllers\Auth\KeycloakController@redirectToKeycloak')->name('auth.keycloak.redirect');
Route::get('/login/keycloak/callback', 'Pterodactyl\Http\Controllers\Auth\KeycloakController@handleKeycloakCallback')->name('auth.keycloak.callback');

// Redirect all login requests to keycloak.
Route::get('/login', 'Pterodactyl\Http\Controllers\Auth\LoginController@index')->name('auth.login');

Route::get('/password', 'Pterodactyl\Http\Controllers\Auth\KeycloakController@redirectToKeycloak')->name('auth.forgot-password');
Route::get('/password/reset/{token}', 'Pterodactyl\Http\Controllers\Auth\KeycloakController@redirectToKeycloak')->name('auth.reset');

Route::post('/logout', 'Pterodactyl\Http\Controllers\Auth\LoginController@logout')
    ->withoutMiddleware('guest')
    ->middleware('auth')
    ->name('auth.logout');

Route::fallback('Pterodactyl\Http\Controllers\Auth\LoginController@index');
