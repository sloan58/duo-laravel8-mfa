## Laravel 8 and Duo MFA Sample App

This app uses a fresh installation of [Laravel 8](https://laravel.com/) to provide an example in using [Cisco Duo's](https://duo.com/) Multi Factor Authentication service.

The workflow is pretty straightforward:

1. A user performs a login to the application.
2. If the user's email domain has been added to the "MFA" domains (i.e. the users of this domain should use MFA), the the typical login flow is interrupted to provide the DUO iFrame prior to application login.
3. The "MFA Domain" user credentials are verified (here it's done locally but this could also be the response from an SSO provider).
4. If the credentials are good, we return the Duo iFrame view which is just a little bit of javascript to provide the Duo MFA experience.
5. After the user performs the MFA operation, the client-side javascript send a callback response to our app, which we can validate and decode to retrieve the users email address.
6. The user is logged into the application and returned to the proper page.

### Dependencies
```html
<script src="/js/Duo-Web-v2.min.js"></script>
```
[Available Here](https://raw.githubusercontent.com/duosecurity/duo_php/master/js/Duo-Web-v2.min.js)

### Environment Variables
`DUO_HOST=`

`DUO_IKEY=`

`DUO_SKEY=`

`DUO_AKEY=`

`DUO_MFA_DOMAINS=`

### Login Controller
`app/Http/Controllers/Auth/LoginController.php`
```php
...

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

....
```

### Callback Function

`routes/web.php`

```php
Route::post('/duo-callback', function() {
    $userEmail = Web::verifyResponse(
        env('DUO_IKEY'),
        env('DUO_SKEY'),
        env('DUO_AKEY'),
        request()->get('sig_response')
    );
    if($user = \App\Models\User::where('email', $userEmail)->first()) {
        auth()->login($user);
        return view('home');
    } else {
        return view('login');
    }
});
```

### Duo iFrame blade view

`resources/views/duo-mfa.blade.php`

```html
@extends('layouts.app')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 text-center">
            <iframe
                id="duo_iframe"
                width="590"
                height="500"
                frameborder="0"
                allowtransparency="true"
                style="background:transparent;">
            </iframe>
        </div>
    </div>
    @push('js')
        <script>
            Duo.init({
                'host': '{{ $duoInfo['host'] }}',
                'sig_request': '{{ $duoInfo['sig'] }}',
                'post_action': '{{ $duoInfo['callback'] }}'
            });
        </script>
    @endpush
@endsection


```
