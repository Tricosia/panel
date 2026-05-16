<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse as LaravelRedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Pterodactyl\Http\Controllers\Controller;
use Pterodactyl\Models\User;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class KeycloakController extends Controller
{
    /**
     * Redirects the user to the Keycloak authentication page.
     */
    public function redirectToKeycloak(): SymfonyRedirectResponse
    {
        return Socialite::driver('keycloak')->redirect();
    }

    /**
     * Handles the callback from Keycloak after authentication.
     */
    public function handleKeycloakCallback(): LaravelRedirectResponse
    {
        try
        {
            $keycloakUser = Socialite::driver('keycloak')->user();
        }
        catch (\Exception $e)
        {
            return redirect()->route('login')->withErrors([
                'error' => 'Keycloak authentication failed.'
            ]);
        }

        $user = User::whereEmail($keycloakUser->getEmail())->first();

        if ($user == null)
        {
            return redirect()->route('login')->withErrors([
                'error' => 'Panel access not permitted.'
            ]);
        }

        Auth::login($user, true);

        return redirect()->intended('/');
    }
}
