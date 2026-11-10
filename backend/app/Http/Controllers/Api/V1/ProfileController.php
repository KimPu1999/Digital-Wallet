<?php
namespace App\Http\Controllers\Api\V1;
use Illuminate\Http\JsonResponse; use Illuminate\Http\Request;
final class ProfileController { public function show(Request $request): JsonResponse { return response()->json(['data'=>$request->user()->customerProfile]); } }
