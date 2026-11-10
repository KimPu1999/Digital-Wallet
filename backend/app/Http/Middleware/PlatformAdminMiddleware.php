<?php

namespace App\Http\Middleware;

use App\Services\PlatformAdminAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class PlatformAdminMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();
        abort_unless($user, 401, 'Authentication required.');
        app(PlatformAdminAccessService::class)->assert($user, $permissions[0] ?? 'dashboard.read');
        return $next($request);
    }
}
