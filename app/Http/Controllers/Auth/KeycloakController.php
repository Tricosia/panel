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
        $baseUrl = config('services.keycloak.base_url', 'https://auth.tricosia.de');
        $realm = config('services.keycloak.realms', 'tricosia');
        $clientId = config('services.keycloak.client_id', 'pterodactyl-panel');
        $redirectUri = config('services.keycloak.redirect', 'https://panel.tricosia.de/auth/login/keycloak/callback');

        $state = \Illuminate\Support\Str::random(40);

        session(['state' => $state]);

        $queryParams = http_build_query([
            'client_id'     => $clientId,
            'redirect_uri'  => $redirectUri,
            'scope'         => 'openid profile email',
            'response_type' => 'code',
            'state'         => $state,
        ]);

        $targetUrl = rtrim($baseUrl, '/') . '/realms/' . $realm . '/protocol/openid-connect/auth?' . $queryParams;

        return redirect()->away($targetUrl);
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
            return redirect()->route('auth.login')->withErrors([
                'error' => 'Keycloak authentication failed.'
            ]);
        }

        if (!$keycloakUser instanceof \Laravel\Socialite\Two\User) {
            abort(500, 'Invalid user object received. Please contact an administrator.');
        }

        $userAttributes = $keycloakUser->getRaw();
        $userRoles = $userAttributes['realm_access']['roles'] ?? [];

        $requiredRole = 'panel-user';
        $adminRole = 'panel-admin';

        if (!in_array($requiredRole, $userRoles) && !in_array($adminRole, $userRoles)) {
            abort(403, 'Access denied. You do not have the required permissions for this panel.');
        }

        $user = User::whereEmail($keycloakUser->getEmail())->first();

        $username = $keycloakUser->getName() ?? explode('@', $keycloakUser->getEmail())[0];

        $fullName = $keycloakUser->getName() ?? 'Keycloak User';
        $nameParts = explode(' ', $fullName, 2);
        $firstName = $nameParts[0] ?? 'Keycloak';
        $lastName = $nameParts[1] ?? 'User';

        // create a new pterodactyl user if none exists
        if (!$user)
        {
            // if a user with this username already exists, append a random number to the username
            if (User::whereUsername($username)->exists())
            {
                $username = $username . '_' . rand(10, 99);
            }

            $user = User::query()->create([
                'email' => $keycloakUser->getEmail(),
                'name' => $username,
                'name_first' => $firstName,
                'name_last' => $lastName,
                'password' => bcrypt(str_random(32)),
                'root_admin' => in_array($adminRole, $userRoles)
            ]);
        }
        else
        {
            $user->update([
                'name' => $username,
                'name_first' => $firstName,
                'name_last' => $lastName,
            ]);
        }

        Auth::login($user, true);

        return redirect()->intended('/');
    }
}
