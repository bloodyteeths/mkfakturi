<?php

namespace App\Http\Controllers\V1\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = AppServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Handle a login request to the application.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function login(Request $request)
    {
        Log::info('Login attempt', [
            'email' => $request->input('email'),
            'has_password' => $request->filled('password'),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Call the parent trait's login method
        return $this->parentLogin($request);
    }

    /**
     * Call parent login method from trait
     */
    protected function parentLogin(Request $request)
    {
        $this->validateLogin($request);

        // If the class is using the ThrottlesLogins trait, we can automatically throttle
        // the login attempts for this application. We'll key this by the username and
        // the IP address of the client making these requests into this application.
        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            Log::info('Login successful', ['email' => $request->input('email')]);

            return $this->sendLoginResponse($request);
        }

        Log::warning('Login failed - credentials mismatch', [
            'email' => $request->input('email'),
            'ip' => $request->ip(),
        ]);

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    /**
     * Send the response after the user was authenticated.
     * Returns user data including role for frontend routing.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();

        $this->clearLoginAttempts($request);

        // Get the authenticated user
        $user = $this->guard()->user();

        // Determine role - check if user is a partner (has partner record)
        $role = $user->role;
        $isPartner = $user->partner()->exists();
        if ($isPartner) {
            $role = 'partner';
        }

        Log::info('Login response', [
            'user_id' => $user->id,
            'email' => $user->email,
            'db_role' => $user->role,
            'is_partner' => $isPartner,
            'returned_role' => $role,
        ]);

        // Return user info in the response so frontend can check role
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->name,
                'role' => $role,
                'is_partner' => $isPartner,
            ],
        ]);
    }
}
// CLAUDE-CHECKPOINT
