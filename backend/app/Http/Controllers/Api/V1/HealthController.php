<?php
namespace App\Http\Controllers\Api\V1;
use Illuminate\Http\JsonResponse;
final class HealthController { public function __invoke(): JsonResponse { return response()->json(['status'=>'ok','service'=>'digital-wallet-api','phase'=>'1-foundation','demo_only'=>true]); } }
