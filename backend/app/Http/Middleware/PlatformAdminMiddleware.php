<?php

namespace App\Http\Middleware;

use App\Services\PlatformAdminAccessService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response;

final class PlatformAdminMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();
        abort_unless($user, 401, 'Authentication required.');
        if (!$request->session()->get('platform_admin_authenticated_at')) {
            return response()->json(['message' => 'Administrator MFA verification is required.', 'mfa_required' => true, 'sandbox_only' => true], 403);
        }
        try {
            app(PlatformAdminAccessService::class)->assert($user, $permissions[0] ?? 'dashboard.read');
        } catch (UnauthorizedException $exception) {
            return response()->json(['message' => $exception->getMessage(), 'sandbox_only' => true], 403);
        }
        return $next($request);
    }
}
