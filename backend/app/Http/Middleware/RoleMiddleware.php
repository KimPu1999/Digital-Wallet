<?php
namespace App\Http\Middleware;
use Closure; use Illuminate\Http\Request; use Symfony\Component\HttpFoundation\Response;
final class RoleMiddleware { public function handle(Request $request,Closure $next,string ...$roles): Response { $user=$request->user(); abort_unless($user && collect($roles)->contains(fn($role)=>$user->hasRole($role)),403,'Forbidden.'); return $next($request); } }
