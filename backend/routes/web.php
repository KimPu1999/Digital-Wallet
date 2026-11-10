<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json(['service'=>'Digital Wallet Platform','phase'=>'1-foundation','demo_only'=>true]));

// Laravel's built-in password notification expects this named route. The
// reset itself remains an API operation; this route only hands the opaque,
// time-limited token to the SPA reset screen.
Route::get('/reset-password/{token}', function (Request $request, string $token) {
    $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
    $query = http_build_query([
        'token' => $token,
        'email' => $request->query('email'),
    ]);

    return redirect()->away($frontendUrl.'/reset-password?'.$query);
})->name('password.reset');
