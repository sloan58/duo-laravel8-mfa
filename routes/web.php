<?php

use Duo\Web;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware(['auth'])->group(function() {
    Route::get('/', function () {
        return view('home');
    });
    Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
});

Auth::routes();

Route::get('duo-mfa', function() {
    return view('duo-mfa');
});

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
