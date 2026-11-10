<?php
use Illuminate\Support\Facades\Route;
Route::get('/', fn () => response()->json(['service'=>'Digital Wallet Platform','phase'=>'1-foundation','demo_only'=>true]));
