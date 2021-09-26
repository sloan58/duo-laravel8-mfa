<?php

namespace App\Http\Controllers\Auth;

use Duo\Web;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use App\Providers\RouteServiceProvider;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

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
    protected $redirectTo = RouteServiceProvider::HOME;

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
     * @param Request $request
     * @return RedirectResponse|Response|JsonResponse
     *
     * @throws ValidationException
     */
    public function login(Request $request)
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

        // Extract the domain from the users login email
        [$name, $domain] = explode('@', $request->get('email'));

        // Check if the domain is part of the DUO MFA Domains array
        // This is set in the demo as a comma separated string
        // But could be a database table of MFA Domains
        if(in_array($domain, explode(',', env('DUO_MFA_DOMAINS')))) {

            // Here, we check that the user's credentials are valid.
            // The demo uses a local database, but this could also be an
            // SSO SAML response or other method of verifying credentials
            if(auth()->validate($request->only($this->username(), 'password'))) {

                // After auth passes, we setup the Duo iFrame information
                $duoInfo = [
                    'host' => env('DUO_HOST'), // The Duo API host (from Duo app portal)
                    'callback' => env('APP_URL') . '/duo-callback',  // The callback to send a response after MFA completes
                    'user' => $request->get('email'),
                    'sig' => Web::signRequest(
                            env('DUO_IKEY'), // The Duo API IKEY host (from Duo app portal)
                            env('DUO_SKEY'), // The Duo API SKEY (from Duo app portal)
                            env('DUO_AKEY'), // The Duo API AKEY (defined locally)
                            $request->get('email')) // The User's email address
                ];

                // Return the Duo MFA iFrame screen
                return view('duo-mfa', compact('duoInfo'));
            } else {
                // This is called if the credentials check was not successful
                $this->incrementLoginAttempts($request);
                return $this->sendFailedLoginResponse($request);
            }
        }

        // The code below is called for non-MFA domains
        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }

        // If the login attempt was unsuccessful we will increment the number of attempts
        // to login and redirect the user back to the login form. Of course, when this
        // user surpasses their maximum number of attempts they will get locked out.
        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }
}
