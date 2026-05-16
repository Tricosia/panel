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
        $code = request()->get('code');
        $state = request()->get('state');

        if (!$code) {
            abort(400, 'No code parameter provided.');
        }

        if (!$state || $state !== session('state')) {
            session()->forget('state');
            abort(400, 'Invalid state parameter.');
        }

        session()->forget('state');

        $baseUrl = config('services.keycloak.base_url', 'https://auth.tricosia.de');
        $realm = config('services.keycloak.realms', 'tricosia');
        $clientId = config('services.keycloak.client_id', 'pterodactyl-panel');
        $clientSecret = config('services.keycloak.client_secret');
        $redirectUri = config('services.keycloak.redirect', 'https://panel.tricosia.de/auth/login/keycloak/callback');

        $tokenUrl = rtrim($baseUrl, '/') . '/realms/' . $realm . '/protocol/openid-connect/token';

        try
        {
            $response = \Illuminate\Support\Facades\Http::withOptions([
                        'verify' => true
                    ])->asForm()->post($tokenUrl, [
                        'grant_type'   => 'authorization_code',
                        'client_id'    => $clientId,
                        'client_secret'=> $clientSecret,
                        'code'         => $code,
                        'redirect_uri' => $redirectUri,
                    ]);

            if ($response->failed()) {
                abort(500, 'Keycloak authentication failed.');
            }

            $tokenData = $response->json();
            $accessToken = $tokenData['access_token'] ?? null;

            if (!$accessToken) {
                abort(500, 'Access token not found.');
            }

            $tokenParts = explode('.', $accessToken);
            if (!isset($tokenParts[1])) {
                abort(500, 'Invalid access token.');
            }

            $userData = json_decode(base64_decode($tokenParts[1]), true) ?? [];
            $userRoles = $userData['resource_access'][$clientId]['roles'] ?? [];

            $allowedUserRole = 'panel-user';
            $allowedAdminRole = 'panel-admin';

            if (!in_array($allowedUserRole, $userRoles) && !in_array($allowedAdminRole, $userRoles)) {
                abort(403, 'Access denied. You do not have the required permissions for this panel.');
            }

            $email = $userData['email'] ?? null;
            if (!$email) {
                abort(400, 'No email address provided by Keycloak.');
            }

            $user = \Pterodactyl\Models\User::where('email', $email)->first();

            $isAdmin = in_array($allowedAdminRole, $userRoles);

            if (!$user) {
                $user = \Pterodactyl\Models\User::create([
                    'external_id' => $userData['sub'] ?? \Illuminate\Support\Str::random(10),
                    'uuid'        => \Webpatser\Uuid\Uuid::generate(4)->string,
                    'username'    => $userData['preferred_username'] ?? head(explode('@', $email)),
                    'email'       => $email,
                    'name_first'  => $userData['given_name'] ?? 'Keycloak',
                    'name_last'   => $userData['family_name'] ?? 'User',
                    'password'    => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(32)),
                    'root_admin'  => $isAdmin,
                ]);
            } else {
                $user->update([
                    'root_admin' => $isAdmin,
                ]);
            }

            \Illuminate\Support\Facades\Auth::login($user, true);
            request()->session()->regenerate();

            return redirect()->to('/');
        }
        catch (\Exception $e)
        {
            return redirect()->route('auth.login')->withErrors([
                'error' => 'Keycloak authentication failed.'
            ]);
        }
    }
}
