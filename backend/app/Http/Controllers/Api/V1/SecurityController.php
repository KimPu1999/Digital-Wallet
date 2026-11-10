<?php
namespace App\Http\Controllers\Api\V1;

use App\Domain\Security\SecurityEventService;
use App\Models\AuthSecurityEvent;
use App\Models\CustomerRiskProfile;
use App\Models\DeviceRegistration;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class SecurityController
{
    public function overview(Request $request): JsonResponse { $user = $request->user(); return response()->json(['data' => ['mfa_enabled' => app(\App\Services\MfaService::class)->enabled($user), 'risk_profile' => CustomerRiskProfile::where('user_id', $user->id)->first(['classification','screening_status','review_due_at']), 'active_sessions' => DB::table('active_sessions')->where('user_id', $user->id)->count(), 'recent_events' => AuthSecurityEvent::where('user_id', $user->id)->latest('occurred_at')->limit(10)->get(['event_type','severity','occurred_at','ip_address']), 'sandbox_only' => true], 'sandbox_only' => true]); }
    public function sessions(Request $request): JsonResponse { return response()->json(['data' => DB::table('active_sessions')->where('user_id', $request->user()->id)->select(['id','ip_address','user_agent','last_seen_at','created_at'])->latest('last_seen_at')->get(), 'sandbox_only' => true]); }
    public function devices(Request $request): JsonResponse { return response()->json(['data' => DeviceRegistration::where('user_id', $request->user()->id)->latest('last_seen_at')->get(['id','label','platform','status','last_seen_at','registered_at']), 'sandbox_only' => true]); }
    public function events(Request $request): JsonResponse { return response()->json(['data' => AuthSecurityEvent::where('user_id', $request->user()->id)->latest('occurred_at')->paginate(10), 'sandbox_only' => true]); }
    public function revokeSession(Request $request, string $session): JsonResponse { $deleted = DB::table('active_sessions')->where('id', $session)->where('user_id', $request->user()->id)->delete(); abort_unless($deleted, 404, 'Session not found.'); app(SecurityEventService::class)->record($request->user()->id, 'session_revoked', $request, 'low', ['session_id' => $session]); return response()->json(['message' => 'Session revoked.', 'sandbox_only' => true]); }
}
