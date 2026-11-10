<?php
namespace App\Http\Controllers\Api\V1;
use Illuminate\Http\JsonResponse; use Illuminate\Http\Request;
final class WalletController { public function index(Request $request): JsonResponse { return response()->json(['data'=>$request->user()->wallets()->with('currency')->get(),'demo_only'=>true]); } }
