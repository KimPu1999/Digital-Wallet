<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
final class RequestCorrelationId { public function handle(Request $request, Closure $next): Response { $id = (string) ($request->header('X-Correlation-ID') ?: Str::uuid()); $request->attributes->set('correlation_id', $id); $response = $next($request); $response->headers->set('X-Correlation-ID', $id); return $response; } }
