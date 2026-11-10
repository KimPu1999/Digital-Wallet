<?php
namespace App\Http\Controllers\Api\V1;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

final class VerificationController
{
    public function resend(Request $request): JsonResponse
    {
        $request->user()->sendEmailVerificationNotification();
        return response()->json(['message'=>'If verification is pending, instructions will be sent.']);
    }

    public function verify(Request $request, string $id, string $hash): Response
    {
        if (!URL::hasValidSignature($request)) return $this->invalidLink($request);
        $user = User::findOrFail($id);
        if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) return $this->invalidLink($request);

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            $user->forceFill(['account_status'=>'active'])->save();
            event(new Verified($user));
        }

        if ($request->expectsJson()) return response()->json(['message'=>'Email verified.']);
        return redirect()->away(rtrim(config('app.frontend_url'), '/').'/admin/login?verified=1');
    }

    private function invalidLink(Request $request): Response
    {
        if ($request->expectsJson()) return response()->json(['message'=>'The verification link is invalid or expired.'], 403);
        return redirect()->away(rtrim(config('app.frontend_url'), '/').'/admin/login?verification=invalid');
    }
}
