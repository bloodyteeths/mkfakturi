<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\OneIdProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


/**
 * Handles Macedonian eID/OneID OpenID Connect authentication flow.
 *
 * Two-step OAuth 2.0 authorization code flow:
 *   1. redirect() — sends user to the OneID authorize endpoint
 *   2. callback() — processes the authorization code, creates/links account, logs in
 *
 * @see \App\Services\Auth\OneIdProvider
 */
class OneIdAuthController extends Controller
{
    /**
     * @var OneIdProvider
     */
    protected OneIdProvider $provider;

    /**
     * Create a new controller instance.
     */
    public function __construct(OneIdProvider $provider)
    {
        $this->provider = $provider;
    }

    /**
     * Redirect the user to the OneID authorization endpoint.
     *
     * Generates a CSRF state token, stores it in the session, and redirects
     * to the OIDC provider's authorize URL.
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirect(Request $request)
    {
        $state = Str::random(40);
        $request->session()->put('oneid_state', $state);

        Log::info('OneID: Redirecting to authorization endpoint', [
            'ip' => $request->ip(),
        ]);

        $url = $this->provider->getAuthorizationUrl($state);

        return redirect()->away($url);
    }

    /**
     * Handle the callback from the OneID authorization server.
     *
     * Validates state, exchanges authorization code for tokens, fetches user
     * info, then either:
     *   - Logs in existing user (matched by oneid_sub)
     *   - Links oneid_sub to existing user (matched by email)
     *   - Creates a new user account
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request)
    {
        // Validate state parameter to prevent CSRF
        $sessionState = $request->session()->pull('oneid_state');

        if (! $sessionState || $sessionState !== $request->input('state')) {
            Log::warning('OneID: Invalid state parameter', [
                'ip' => $request->ip(),
                'has_session_state' => ! empty($sessionState),
                'has_request_state' => $request->has('state'),
            ]);

            return redirect('/login')->with('error', 'Invalid authentication state. Please try again.');
        }

        // Check for error response from provider
        if ($request->has('error')) {
            Log::warning('OneID: Authorization error from provider', [
                'error' => $request->input('error'),
                'error_description' => $request->input('error_description'),
            ]);

            return redirect('/login')->with('error', 'Authentication was denied or failed. Please try again.');
        }

        // Validate authorization code is present
        $code = $request->input('code');
        if (! $code) {
            Log::warning('OneID: Missing authorization code in callback', [
                'ip' => $request->ip(),
            ]);

            return redirect('/login')->with('error', 'Missing authorization code. Please try again.');
        }

        try {
            // Exchange code for tokens
            $tokens = $this->provider->exchangeCode($code);

            // Fetch user profile from OIDC userinfo endpoint
            $userInfo = $this->provider->getUserInfo($tokens['access_token']);

            if (empty($userInfo['sub'])) {
                Log::error('OneID: Empty subject identifier in userinfo response');

                return redirect('/login')->with('error', 'Could not retrieve your identity. Please try again.');
            }

            // Attempt to find or create the user
            $user = $this->findOrCreateUser($userInfo);

            // Log the user in
            Auth::login($user, true);
            $request->session()->regenerate();

            Log::info('OneID: User logged in successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'oneid_sub' => $user->oneid_sub,
            ]);

            return redirect('/admin/dashboard');

        } catch (\RuntimeException $e) {
            Log::error('OneID: Authentication flow failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return redirect('/login')->with('error', 'Authentication failed. Please try again later.');
        }
    }

    /**
     * Find an existing user or create a new one based on OneID profile data.
     *
     * Lookup priority:
     *   1. Match by oneid_sub (already linked)
     *   2. Match by email (link oneid_sub to existing account)
     *   3. Create new user
     *
     * @param  array{sub: string, name: string|null, email: string|null, phone: string|null}  $userInfo
     * @return User
     */
    protected function findOrCreateUser(array $userInfo): User
    {
        $sub = $userInfo['sub'];

        // 1. Check if user already linked with this OneID subject
        $user = User::where('oneid_sub', $sub)->first();

        if ($user) {
            Log::info('OneID: Found existing user by oneid_sub', [
                'user_id' => $user->id,
                'oneid_sub' => $sub,
            ]);

            return $user;
        }

        // 2. Check if a user with the same email exists — link the account
        if (! empty($userInfo['email'])) {
            $user = User::where('email', $userInfo['email'])->first();

            if ($user) {
                $user->oneid_sub = $sub;
                $user->save();

                Log::info('OneID: Linked oneid_sub to existing user by email', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'oneid_sub' => $sub,
                ]);

                return $user;
            }
        }

        // 3. Create a new user account
        $user = User::create([
            'name' => $userInfo['name'] ?? 'OneID User',
            'email' => $userInfo['email'],
            'phone' => $userInfo['phone'],
            'oneid_sub' => $sub,
            'role' => 'user',
            'password' => null, // No password — eID-only login
        ]);

        Log::info('OneID: Created new user from eID profile', [
            'user_id' => $user->id,
            'email' => $user->email,
            'oneid_sub' => $sub,
        ]);

        return $user;
    }
}
