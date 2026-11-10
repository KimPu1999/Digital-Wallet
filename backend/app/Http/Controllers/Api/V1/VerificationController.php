<?php
namespace App\Http\Controllers\Api\V1;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

final class VerificationController
{
    public function resend(Request $request): JsonResponse
    {
        $request->user()->sendEmailVerificationNotification();
        return response()->json(['message'=>'If verification is pending, instructions will be sent.']);
    }

    public function verify(Request $request, string $id, string $hash): JsonResponse
    {
        abort_unless(URL::hasValidSignature($request), 403, 'Invalid verification link.');
        $user = User::findOrFail($id);
        abort_unless(hash_equals(sha1($user->getEmailForVerification()), $hash), 403, 'Invalid verification link.');

        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            $user->forceFill(['account_status'=>'active'])->save();
            event(new Verified($user));
        }

        return response()->json(['message'=>'Email verified.']);
    }
}
