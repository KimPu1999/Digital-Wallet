<?php
namespace App\Http\Controllers\Api\V1;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
final class HealthController { public function __invoke(): JsonResponse { $database = 'ok'; $redis = 'not_configured'; try { DB::select('select 1'); } catch (\Throwable) { $database = 'unavailable'; } if (config('queue.default') === 'redis' || env('REDIS_HEALTHCHECK', false)) { try { Redis::connection('default')->ping(); $redis = 'ok'; } catch (\Throwable) { $redis = 'unavailable'; } } $status = $database === 'ok' && !in_array($redis, ['unavailable'], true) ? 'ok' : 'degraded'; return response()->json(['status'=>$status,'service'=>'digital-wallet-api','phase'=>'13-infrastructure','dependencies'=>['database'=>$database,'redis'=>$redis],'demo_only'=>true], $status === 'ok' ? 200 : 503); } }
