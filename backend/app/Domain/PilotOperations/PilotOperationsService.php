<?php

namespace App\Domain\PilotOperations;

use App\Models\KycCase;
use App\Models\PilotProgram;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PilotOperationsService
{
    private const FEATURES = ['sandbox_wallet_checkout', 'sandbox_provider_checkout', 'sandbox_qr', 'sandbox_fx_quote', 'sandbox_transfer_simulation', 'read_only_reporting'];

    public function programs() { return PilotProgram::query()->latest('created_at')->paginate(10); }

    public function program(string $id): array
    {
        $program = PilotProgram::findOrFail($id);
        return ['program' => $program, 'scope' => DB::table('pilot_scope_versions')->where('pilot_program_id', $id)->latest('version')->first(), 'counts' => ['cohorts' => DB::table('pilot_cohorts')->where('pilot_program_id', $id)->count(), 'participants' => DB::table('pilot_participants')->where('pilot_program_id', $id)->count(), 'active_participants' => DB::table('pilot_participants')->where('pilot_program_id', $id)->where('status', 'active')->count(), 'open_exceptions' => DB::table('pilot_exceptions')->where('pilot_program_id', $id)->whereIn('status', ['open', 'investigating', 'escalated'])->count()]];
    }

    public function overview(string $id): array
    {
        $program = PilotProgram::findOrFail($id);
        $events = DB::table('pilot_activity_events')->where('pilot_program_id', $id);
        $providerHealth = Schema::hasTable('payment_providers') ? DB::table('payment_providers')->where('environment', 'sandbox')->select(['code', 'health_status', 'enabled'])->get() : collect();
        return [
            'generated_at' => now()->toIso8601String(),
            'sandbox_only' => true,
            'pilot_status' => $program->status,
            'environment' => $program->environment,
            'participants' => DB::table('pilot_participants')->where('pilot_program_id', $id)->count(),
            'active_cohorts' => DB::table('pilot_cohorts')->where('pilot_program_id', $id)->where('status', 'active')->count(),
            'sandbox_transaction_count' => (clone $events)->count(),
            'sandbox_volume_by_currency' => (clone $events)->whereNotNull('currency_code')->select('currency_code', DB::raw('sum(coalesce(amount_minor, 0)) as volume_minor'), DB::raw('count(*) as total'))->groupBy('currency_code')->orderBy('currency_code')->get(),
            'operational_exceptions' => DB::table('pilot_exceptions')->where('pilot_program_id', $id)->whereIn('status', ['open', 'investigating', 'escalated'])->count(),
            'pending_reviews' => DB::table('pilot_eligibility_checks')->whereIn('status', ['pending', 'review'])->whereIn('participant_id', DB::table('pilot_participants')->where('pilot_program_id', $id)->select('id'))->count(),
            'open_incidents' => Schema::hasTable('operational_incidents') ? DB::table('operational_incidents')->whereIn('status', ['open', 'investigating', 'mitigating'])->count() : 0,
            'provider_sandbox_health' => $providerHealth,
            'freshness' => now()->toIso8601String(),
        ];
    }

    public function cohorts(string $id) { PilotProgram::findOrFail($id); return DB::table('pilot_cohorts')->where('pilot_program_id', $id)->latest('created_at')->paginate(10); }
    public function participants(string $id) { PilotProgram::findOrFail($id); return DB::table('pilot_participants')->where('pilot_program_id', $id)->latest('created_at')->paginate(10); }
    public function eligibility(string $id) { PilotProgram::findOrFail($id); return DB::table('pilot_eligibility_checks')->whereIn('participant_id', DB::table('pilot_participants')->where('pilot_program_id', $id)->select('id'))->latest('checked_at')->paginate(10); }
    public function limits(string $id) { PilotProgram::findOrFail($id); return DB::table('pilot_limits')->where('pilot_program_id', $id)->latest('updated_at')->paginate(10); }
    public function activity(string $id) { PilotProgram::findOrFail($id); return DB::table('pilot_activity_events')->where('pilot_program_id', $id)->latest('occurred_at')->paginate(10); }
    public function monitoring(string $id) { PilotProgram::findOrFail($id); return DB::table('pilot_monitoring_snapshots')->where('pilot_program_id', $id)->latest('generated_at')->paginate(10); }
    public function reconciliation(string $id) { PilotProgram::findOrFail($id); return DB::table('pilot_reconciliation_reviews')->where('pilot_program_id', $id)->latest('reviewed_at')->latest('created_at')->paginate(10); }
    public function exceptions(string $id) { PilotProgram::findOrFail($id); return DB::table('pilot_exceptions')->where('pilot_program_id', $id)->latest('created_at')->paginate(10); }
    public function reports(string $id): array
    {
        PilotProgram::findOrFail($id);
        return ['generated_at' => now()->toIso8601String(), 'sandbox_only' => true, 'events_by_type' => DB::table('pilot_activity_events')->where('pilot_program_id', $id)->select('event_type', DB::raw('count(*) as total'))->groupBy('event_type')->orderBy('event_type')->get(), 'outcomes_by_status' => DB::table('pilot_activity_events')->where('pilot_program_id', $id)->select('status', DB::raw('count(*) as total'))->groupBy('status')->orderBy('status')->get(), 'volume_by_currency' => DB::table('pilot_activity_events')->where('pilot_program_id', $id)->whereNotNull('currency_code')->select('currency_code', DB::raw('sum(coalesce(amount_minor, 0)) as volume_minor'))->groupBy('currency_code')->orderBy('currency_code')->get(), 'exceptions_by_severity' => DB::table('pilot_exceptions')->where('pilot_program_id', $id)->select('severity', DB::raw('count(*) as total'))->groupBy('severity')->orderBy('severity')->get()];
    }
    public function evaluations(string $id) { PilotProgram::findOrFail($id); return DB::table('pilot_evaluations')->where('pilot_program_id', $id)->latest('created_at')->paginate(10); }
    public function decisions(string $id) { PilotProgram::findOrFail($id); return DB::table('pilot_decisions')->where('pilot_program_id', $id)->latest('created_at')->paginate(10); }

    public function createProgram(array $data, string $actorId): PilotProgram
    {
        $this->assertSandboxScope($data['environment'] ?? 'sandbox', $data['allowed_features'] ?? []);
        return DB::transaction(function () use ($data, $actorId): PilotProgram {
            $program = PilotProgram::create([
                'pilot_reference' => 'PILOT-'.strtoupper(Str::random(12)),
                'name' => $data['name'], 'objective' => $data['objective'], 'environment' => 'sandbox', 'status' => 'draft',
                'jurisdictions' => $data['jurisdictions'] ?? [], 'supported_currencies' => array_map('strtoupper', $data['supported_currencies'] ?? []),
                'allowed_features' => $data['allowed_features'] ?? [], 'provider_capabilities' => $data['provider_capabilities'] ?? [],
                'transaction_types' => $data['transaction_types'] ?? [], 'starts_at' => $data['starts_at'] ?? null, 'ends_at' => $data['ends_at'] ?? null,
                'owner_id' => $data['owner_id'] ?? $actorId, 'reviewer_id' => $data['reviewer_id'] ?? null, 'pilot_authorization_id' => $data['pilot_authorization_id'] ?? null,
                'metadata' => ['sandbox_only' => true], 'sandbox_only' => true,
            ]);
            DB::table('pilot_scope_versions')->insert(['id' => (string) Str::uuid(), 'pilot_program_id' => $program->id, 'version' => 1, 'status' => 'draft', 'jurisdictions' => json_encode($program->jurisdictions), 'currencies' => json_encode($program->supported_currencies), 'features' => json_encode($program->allowed_features), 'provider_capabilities' => json_encode($program->provider_capabilities), 'transaction_types' => json_encode($program->transaction_types), 'created_by' => $actorId, 'created_at' => now(), 'updated_at' => now()]);
            return $program;
        });
    }

    public function updateDraft(PilotProgram $program, array $data): PilotProgram
    {
        if (!in_array($program->status, ['draft', 'planned'], true)) throw ValidationException::withMessages(['pilot' => 'Only draft or planned pilots can be edited.']);
        $this->assertSandboxScope($data['environment'] ?? $program->environment, $data['allowed_features'] ?? $program->allowed_features ?? []);
        unset($data['environment'], $data['status'], $data['pilot_reference'], $data['sandbox_only']);
        if (isset($data['supported_currencies'])) $data['supported_currencies'] = array_map('strtoupper', $data['supported_currencies']);
        $program->update($data);
        return $program->refresh();
    }

    public function createCohort(PilotProgram $program, array $data, string $actorId): object
    {
        if (in_array($program->status, ['paused', 'closed', 'completed'], true)) throw ValidationException::withMessages(['pilot' => 'The pilot does not accept cohort changes in its current state.']);
        $id = (string) Str::uuid();
        DB::table('pilot_cohorts')->insert(['id' => $id, 'pilot_program_id' => $program->id, 'name' => $data['name'], 'cohort_type' => $data['cohort_type'], 'status' => 'active', 'eligibility_requirements' => json_encode($data['eligibility_requirements'] ?? []), 'max_participants' => $data['max_participants'] ?? null, 'created_by' => $actorId, 'created_at' => now(), 'updated_at' => now()]);
        return DB::table('pilot_cohorts')->where('id', $id)->first();
    }

    public function enroll(PilotProgram $program, array $data, string $actorId): array
    {
        $exists = DB::table('pilot_participants')->where(['pilot_program_id' => $program->id, 'subject_type' => $data['subject_type'], 'subject_id' => $data['subject_id']])->exists();
        if ($exists) throw ValidationException::withMessages(['participant' => 'This subject is already enrolled in the pilot.']);
        if (!empty($data['cohort_id'])) { $cohort = DB::table('pilot_cohorts')->where('id', $data['cohort_id'])->where('pilot_program_id', $program->id)->first(); if (!$cohort || $cohort->status !== 'active') throw ValidationException::withMessages(['cohort_id' => 'The cohort is not active or does not belong to this pilot.']); }
        $participantId = (string) Str::uuid();
        DB::table('pilot_participants')->insert(['id' => $participantId, 'pilot_program_id' => $program->id, 'cohort_id' => $data['cohort_id'] ?? null, 'subject_type' => $data['subject_type'], 'subject_id' => $data['subject_id'], 'status' => 'invited', 'eligibility_status' => 'pending', 'invited_by' => $actorId, 'invited_at' => now(), 'metadata' => json_encode(['sandbox_only' => true]), 'created_at' => now(), 'updated_at' => now()]);
        $checks = $this->evaluateParticipant($program, $participantId, $actorId);
        return ['participant' => DB::table('pilot_participants')->where('id', $participantId)->first(), 'checks' => $checks];
    }

    public function evaluateParticipant(PilotProgram $program, string $participantId, ?string $actorId = null): array
    {
        $participant = DB::table('pilot_participants')->where('id', $participantId)->where('pilot_program_id', $program->id)->firstOrFail();
        $checks = [];
        $checks[] = ['check_type' => 'sandbox_environment', 'status' => $program->environment === 'sandbox' && $program->sandbox_only ? 'passed' : 'failed', 'reason' => 'Pilot environment must remain sandbox.'];
        $checks[] = ['check_type' => 'pilot_schedule', 'status' => (!$program->starts_at || now()->greaterThanOrEqualTo($program->starts_at)) && (!$program->ends_at || now()->lessThanOrEqualTo($program->ends_at)) ? 'passed' : 'failed', 'reason' => 'Pilot window must be active.'];
        $checks[] = ['check_type' => 'pilot_status', 'status' => in_array($program->status, ['planned', 'active'], true) ? 'passed' : 'review', 'reason' => 'Pilot must be planned or active before activity.'];
        if ($participant->subject_type === 'customer') {
            $user = User::find($participant->subject_id);
            $checks[] = ['check_type' => 'account_authorization', 'status' => $user && $user->is_active && $user->account_status !== 'suspended' ? 'passed' : 'failed', 'reason' => 'Customer account must be active.'];
            $checks[] = ['check_type' => 'email_verification', 'status' => $user?->email_verified_at ? 'passed' : 'failed', 'reason' => 'Verified email is required.'];
            $kycRequired = $user && Schema::hasTable('country_capabilities') ? (bool) DB::table('country_capabilities')->where('country_code', $user->country_code)->value('kyc_enabled') : false;
            $kycPassed = !$kycRequired || KycCase::where('user_id', $participant->subject_id)->where('status', 'approved')->exists();
            $checks[] = ['check_type' => 'kyc_status', 'status' => $kycPassed ? 'passed' : 'failed', 'reason' => $kycRequired ? 'Approved KYC is required by the configured country capability.' : 'KYC requirement is not configured for this country.'];
            $highRisk = Schema::hasTable('risk_cases') && DB::table('risk_cases')->where('subject_type', 'user')->where('subject_id', $participant->subject_id)->whereIn('status', ['open', 'investigating'])->whereIn('severity', ['high', 'critical'])->exists();
            $checks[] = ['check_type' => 'risk_restriction', 'status' => $highRisk ? 'failed' : 'passed', 'reason' => $highRisk ? 'Open high-risk case blocks pilot eligibility.' : 'No open high-risk case found.'];
        } elseif ($participant->subject_type === 'merchant') {
            $merchant = DB::table('merchants')->where('id', $participant->subject_id)->first();
            $verified = Schema::hasTable('merchant_verifications') && DB::table('merchant_verifications')->where('merchant_id', $participant->subject_id)->where('status', 'approved')->exists();
            $checks[] = ['check_type' => 'account_authorization', 'status' => $merchant && in_array($merchant->status, ['active', 'approved'], true) ? 'passed' : 'failed', 'reason' => 'Merchant must be active or approved.'];
            $checks[] = ['check_type' => 'kyb_status', 'status' => $verified ? 'passed' : 'failed', 'reason' => 'Approved KYB is required for merchant participation.'];
        } else {
            $checks[] = ['check_type' => 'internal_tester_authorization', 'status' => 'passed', 'reason' => 'Internal tester access is explicitly scoped.'];
        }
        foreach ($checks as $check) DB::table('pilot_eligibility_checks')->updateOrInsert(['participant_id' => $participantId, 'check_type' => $check['check_type']], ['id' => (string) Str::uuid(), 'status' => $check['status'], 'reason' => $check['reason'], 'checked_by' => $actorId, 'checked_at' => now(), 'updated_at' => now(), 'created_at' => now()]);
        $eligible = collect($checks)->every(fn (array $check): bool => $check['status'] === 'passed');
        DB::table('pilot_participants')->where('id', $participantId)->update(['eligibility_status' => $eligible ? 'eligible' : 'ineligible', 'status' => $eligible ? 'enrolled' : 'invited', 'enrolled_at' => $eligible ? now() : null, 'updated_at' => now()]);
        return DB::table('pilot_eligibility_checks')->where('participant_id', $participantId)->orderBy('check_type')->get()->all();
    }

    public function assertWithinLimits(PilotProgram $program, string $currency, int $amountMinor, string $eventType, ?string $participantId = null): void
    {
        if ($amountMinor <= 0) throw ValidationException::withMessages(['amount' => 'Pilot activity amount must be a positive integer minor-unit value.']);
        if (!$program->sandbox_only || $program->environment !== 'sandbox') throw ValidationException::withMessages(['pilot' => 'Only sandbox pilot activity is permitted.']);
        if (!in_array(strtoupper($currency), array_map('strtoupper', $program->supported_currencies ?? []), true)) throw ValidationException::withMessages(['currency' => 'Currency is outside the pilot scope.']);
        $limit = DB::table('pilot_limits')->where('pilot_program_id', $program->id)->where('status', 'active')->where(function ($query) use ($currency): void { $query->where('currency_code', strtoupper($currency))->orWhereNull('currency_code'); })->orderByRaw('currency_code is null')->first();
        if (!$limit) return;
        if ($limit->max_transaction_minor !== null && $amountMinor > (int) $limit->max_transaction_minor) throw ValidationException::withMessages(['amount' => 'Pilot transaction limit exceeded.']);
        $dayStart = now()->startOfDay();
        $events = DB::table('pilot_activity_events')->where('pilot_program_id', $program->id)->where('currency_code', strtoupper($currency))->where('occurred_at', '>=', $dayStart);
        if ($limit->daily_volume_minor !== null && ((int) $events->sum('amount_minor') + $amountMinor) > (int) $limit->daily_volume_minor) throw ValidationException::withMessages(['amount' => 'Pilot daily volume limit exceeded.']);
        if ($limit->daily_activity_count !== null && ((int) $events->count() + 1) > (int) $limit->daily_activity_count) throw ValidationException::withMessages(['activity' => 'Pilot daily activity limit exceeded.']);
        if ($eventType === 'transfer' && $limit->transfer_frequency_per_day !== null && (int) (clone $events)->where('event_type', 'transfer')->count() + 1 > (int) $limit->transfer_frequency_per_day) throw ValidationException::withMessages(['activity' => 'Pilot transfer frequency limit exceeded.']);
        if ($eventType === 'merchant_payment' && $limit->merchant_payment_count_per_day !== null && (int) (clone $events)->where('event_type', 'merchant_payment')->count() + 1 > (int) $limit->merchant_payment_count_per_day) throw ValidationException::withMessages(['activity' => 'Pilot merchant payment limit exceeded.']);
        if ($eventType === 'fx_conversion' && $limit->fx_conversion_count_per_day !== null && (int) (clone $events)->where('event_type', 'fx_conversion')->count() + 1 > (int) $limit->fx_conversion_count_per_day) throw ValidationException::withMessages(['activity' => 'Pilot FX conversion limit exceeded.']);
    }

    public function pause(PilotProgram $program, string $actorId, string $reason): PilotProgram
    {
        if (in_array($program->status, ['closed', 'completed'], true)) throw ValidationException::withMessages(['pilot' => 'Closed pilots cannot be paused.']);
        $metadata = $program->metadata ?? [];
        $metadata['pause_reason'] = $reason;
        $metadata['paused_by'] = $actorId;
        $program->update(['status' => 'paused', 'metadata' => $metadata]);
        return $program->refresh();
    }

    private function assertSandboxScope(string $environment, array $features): void
    {
        if ($environment !== 'sandbox') throw ValidationException::withMessages(['environment' => 'Pilot programs are restricted to the sandbox environment.']);
        $unsupported = array_diff($features, self::FEATURES);
        if ($unsupported) throw ValidationException::withMessages(['allowed_features' => 'The pilot contains unsupported or non-sandbox capabilities.']);
    }
}
