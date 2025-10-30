<?php

namespace App\Http\Controllers\V1\Installation;

use App\Http\Controllers\Controller;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $user = User::where('role', 'super admin')->first();
        Auth::login($user);

        // Generate API token for subsequent requests
        $token = $user->createToken('installation-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'user' => $user,
            'company' => $user->companies()->first(),
            'token' => 'Bearer ' . $token,
        ]);
    }
}
