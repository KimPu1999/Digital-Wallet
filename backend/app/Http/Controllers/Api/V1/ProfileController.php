<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\PersonalProfileRequest;
use App\Http\Requests\PrivacyRequestRequest;
use App\Http\Requests\UserPreferenceRequest;
use App\Models\UserPreference;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ProfileController
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('customerProfile', 'preferences');
        return response()->json(['data' => $this->profile($user), 'sandbox_only' => true]);
    }

    public function update(PersonalProfileRequest $request, AuditLogger $audit): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        if (array_key_exists('phone', $data) && $data['phone'] !== ($user->phone_e164 ?: $user->customerProfile?->phone)) {
            throw ValidationException::withMessages(['phone' => 'Phone changes require a verified phone challenge.']);
        }
        $profileData = collect($data)->only(['display_name', 'phone', 'date_of_birth', 'address_line1', 'address_line2', 'city', 'postal_code'])->all();
        DB::transaction(function () use ($user, $data, $profileData): void {
            if (array_key_exists('name', $data)) $user->update(['name' => $data['name']]);
            if (array_key_exists('phone', $profileData)) {
                $user->update(['phone_e164' => $profileData['phone']]);
            }
            $user->customerProfile()->updateOrCreate(['user_id' => $user->id], $profileData);
        });
        $audit->record('customer.profile_updated', $request, ['user_id' => $user->id, 'fields' => array_keys($data)]);
        return $this->show($request);
    }

    public function preferences(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->preference($request->user()), 'sandbox_only' => true]);
    }

    public function updatePreferences(UserPreferenceRequest $request, AuditLogger $audit): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        if (!empty($data['preferred_wallet_id'])) abort_unless($user->wallets()->whereKey($data['preferred_wallet_id'])->exists(), 422, 'The preferred wallet is not owned by this account.');
        if (!empty($data['default_currency']) && !$user->wallets()->whereHas('currency', fn ($query) => $query->where('code', strtoupper($data['default_currency'])))->exists()) abort(422, 'The selected currency is not enabled for this account.');
        $data['default_currency'] = isset($data['default_currency']) ? strtoupper($data['default_currency']) : null;
        $preference = $user->preferences()->updateOrCreate(['user_id' => $user->id], $data);
        $audit->record('customer.preferences_updated', $request, ['user_id' => $user->id, 'fields' => array_keys($data)]);
        return response()->json(['data' => $preference->refresh(), 'sandbox_only' => true]);
    }

    public function changePassword(ChangePasswordRequest $request, AuditLogger $audit): JsonResponse
    {
        $user = $request->user();
        $user->forceFill(['password' => Hash::make($request->string('password')->toString()), 'remember_token' => Str::random(60)])->save();
        DB::table('active_sessions')->where('user_id', $user->id)->delete();
        $audit->record('customer.password_changed', $request, ['user_id' => $user->id]);
        return response()->json(['message' => 'Password updated. Other sessions were signed out.', 'sandbox_only' => true]);
    }

    public function privacyRequests(Request $request): JsonResponse
    {
        abort_unless(DB::getSchemaBuilder()->hasTable('privacy_requests'), 503, 'Privacy request service is unavailable.');
        return response()->json(['data' => DB::table('privacy_requests')->where('subject_user_id', $request->user()->id)->latest('created_at')->paginate(10), 'sandbox_only' => true]);
    }

    public function createPrivacyRequest(PrivacyRequestRequest $request, AuditLogger $audit): JsonResponse
    {
        abort_unless(DB::getSchemaBuilder()->hasTable('privacy_requests'), 503, 'Privacy request service is unavailable.');
        $id = (string) Str::uuid();
        DB::table('privacy_requests')->insert(['id' => $id, 'subject_user_id' => $request->user()->id, 'request_type' => $request->string('request_type')->toString(), 'scope_summary' => $request->input('scope_summary'), 'status' => 'submitted', 'verification_status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        if (DB::getSchemaBuilder()->hasTable('privacy_request_events')) DB::table('privacy_request_events')->insert(['id' => (string) Str::uuid(), 'privacy_request_id' => $id, 'actor_id' => $request->user()->id, 'event_type' => 'submitted', 'reason' => 'Customer submitted a privacy request.', 'created_at' => now()]);
        $audit->record('customer.privacy_request_created', $request, ['user_id' => $request->user()->id, 'request_id' => $id, 'request_type' => $request->string('request_type')->toString()]);
        return response()->json(['data' => DB::table('privacy_requests')->where('id', $id)->first(), 'sandbox_only' => true], 201);
    }

    private function profile($user): array
    {
        return ['id' => $user->id, 'name' => $user->name, 'display_name' => $user->customerProfile?->display_name, 'email' => $user->email, 'email_verified' => $user->hasVerifiedEmail(), 'phone' => $user->phone_e164 ?: $user->customerProfile?->phone, 'country_code' => $user->country_code, 'account_status' => $user->account_status, 'profile' => $user->customerProfile, 'preferences' => $this->preference($user)];
    }

    private function preference($user): UserPreference
    {
        return $user->preferences()->firstOrCreate(['user_id' => $user->id]);
    }
}
