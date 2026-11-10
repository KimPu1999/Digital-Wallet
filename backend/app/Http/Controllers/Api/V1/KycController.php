<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\KycDocumentRequest;
use App\Http\Requests\KycDraftRequest;
use App\Http\Requests\KycSubmitRequest;
use App\Models\Country;
use App\Models\KycCase;
use App\Services\AuditLogger;
use App\Services\CountryEligibilityService;
use App\Services\PrivateDocumentStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

final class KycController
{
    public function show(Request $request): JsonResponse
    {
        $case = $this->caseFor($request);

        return response()->json(['data' => [
            'case' => $case->load(['documents', 'statusEvents']),
            'policy' => $this->policy($request->user()->country_code),
            'sandbox_only' => true,
        ], 'sandbox_only' => true]);
    }

    public function countries(): JsonResponse
    {
        $countries = Country::with('capabilities')->where('is_active', true)->orderBy('name')->get()->map(fn (Country $country) => [
            'code' => $country->code,
            'name' => $country->name,
            'kyc_enabled' => (bool) $country->capabilities?->kyc_enabled,
        ]);

        return response()->json(['data' => $countries, 'sandbox_only' => true]);
    }

    public function documentTypes(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->policy($request->user()->country_code)['document_types'], 'sandbox_only' => true]);
    }

    public function updateDraft(KycDraftRequest $request, AuditLogger $audit): JsonResponse
    {
        $case = $this->caseFor($request);
        $this->assertDraftEditable($case);
        $details = array_filter($request->validated(), static fn ($value) => $value !== null && $value !== '');
        $details = array_merge($case->personal_details ?? [], $details);
        foreach (['nationality', 'residence_country_code'] as $field) {
            if (isset($details[$field])) $details[$field] = strtoupper((string) $details[$field]);
        }

        $from = $case->status;
        $case->forceFill([
            'personal_details' => $details,
            'status' => in_array($case->status, ['not_started', 'additional_information_required', 'rejected'], true) ? 'draft' : $case->status,
            'last_customer_action_at' => now(),
        ])->save();
        $this->recordStatus($case, $from, $case->status, $request->user()->id, 'Customer draft updated');
        $audit->record('customer.kyc.draft_updated', $request, ['kyc_case_id' => $case->id, 'sandbox_only' => true]);

        return response()->json(['data' => $case->fresh(['documents', 'statusEvents']), 'message' => 'KYC draft saved.', 'sandbox_only' => true]);
    }

    public function submitDocument(KycDocumentRequest $request, CountryEligibilityService $eligibility, PrivateDocumentStorage $storage, AuditLogger $audit): JsonResponse
    {
        $user = $request->user();
        $this->assertKycAvailable($user->country_code, $eligibility);
        $case = $this->caseFor($request);
        $this->assertDraftEditable($case);
        $policy = $this->policy($user->country_code);
        $documentType = $request->string('document_type')->toString();
        abort_unless(collect($policy['document_types'])->contains('code', $documentType), 422, 'This document type is not configured for your country.');

        $file = $request->file('document');
        $key = $storage->put($file, 'kyc/'.$case->id);
        $document = $case->documents()->create(['document_type' => $documentType, 'storage_key' => $key, 'mime_type' => $file->getMimeType(), 'size_bytes' => $file->getSize(), 'scan_status' => 'pending', 'review_status' => 'pending', 'uploaded_at' => now()]);
        $audit->record('customer.kyc.document_uploaded', $request, ['kyc_case_id' => $case->id, 'document_id' => $document->id, 'sandbox_only' => true]);

        return response()->json(['data' => ['document' => $document, 'status' => $case->status], 'message' => 'Document received for security scanning. Upload does not mean approval.', 'sandbox_only' => true], 201);
    }

    public function deleteDocument(Request $request, string $id, AuditLogger $audit): JsonResponse
    {
        $case = $this->caseFor($request);
        $this->assertDraftEditable($case);
        $document = $case->documents()->whereKey($id)->firstOrFail();
        abort_unless($document->review_status === 'pending', 422, 'This document is locked for review and cannot be removed.');
        Storage::disk('local')->delete($document->storage_key);
        $document->delete();
        $audit->record('customer.kyc.document_removed', $request, ['kyc_case_id' => $case->id, 'document_id' => $id, 'sandbox_only' => true]);

        return response()->json(['message' => 'Document removed from the draft.', 'sandbox_only' => true]);
    }

    public function submit(KycSubmitRequest $request, CountryEligibilityService $eligibility, AuditLogger $audit): JsonResponse
    {
        $user = $request->user();
        $this->assertKycAvailable($user->country_code, $eligibility);
        $case = $this->caseFor($request);
        $this->assertDraftEditable($case);
        $policy = $this->policy($user->country_code);
        $details = $case->personal_details ?? [];
        $requiredFields = collect($policy['required_fields'])->filter(fn ($item) => $item['required'])->pluck('code');
        $missing = $requiredFields->filter(fn ($field) => blank($details[$field] ?? null))->values()->all();
        if ($missing !== [] || $policy['document_types'] === []) {
            throw ValidationException::withMessages(['kyc' => $policy['document_types'] === [] ? 'KYC document policy is not configured for this country.' : 'Complete all required identity fields before submitting.', 'missing' => $missing]);
        }
        abort_if($case->documents()->where('review_status', 'pending')->count() === 0, 422, 'Upload at least one document before submitting.');

        DB::transaction(function () use ($case, $request): void {
            $from = $case->status;
            $case->forceFill(['status' => 'submitted', 'consented_at' => now(), 'submitted_at' => now(), 'last_customer_action_at' => now()])->save();
            $this->recordStatus($case, $from, 'submitted', $request->user()->id, 'Customer submitted KYC application');
        });
        $audit->record('customer.kyc.submitted', $request, ['kyc_case_id' => $case->id, 'sandbox_only' => true]);

        return response()->json(['data' => $case->fresh(['documents', 'statusEvents']), 'message' => 'KYC application submitted for authorized review. Approval is not automatic.', 'sandbox_only' => true]);
    }

    public function status(Request $request): JsonResponse
    {
        $case = $this->caseFor($request);

        return response()->json(['data' => ['status' => $case->status, 'submitted_at' => $case->submitted_at, 'reviewed_at' => $case->reviewed_at, 'last_customer_action_at' => $case->last_customer_action_at, 'events' => $case->statusEvents()->latest()->get()], 'sandbox_only' => true]);
    }

    private function caseFor(Request $request): KycCase
    {
        return KycCase::firstOrCreate(['user_id' => $request->user()->id], ['status' => 'not_started', 'country_code' => $request->user()->country_code]);
    }

    private function policy(?string $countryCode): array
    {
        $countryCode = strtoupper((string) $countryCode);
        $country = Country::with('capabilities')->whereKey($countryCode)->where('is_active', true)->first();
        $requirements = $country ? DB::table('kyc_requirements')->where('country_code', $countryCode)->where('subject_type', 'customer')->orderBy('requirement_key')->get() : collect();
        $requiredFields = $requirements->filter(fn ($item) => !str_starts_with($item->requirement_key, 'document:'))->map(fn ($item) => ['code' => $item->requirement_key, 'label' => $item->label, 'required' => (bool) $item->is_required])->values()->all();
        $documentTypes = $requirements->filter(fn ($item) => str_starts_with($item->requirement_key, 'document:'))->map(fn ($item) => ['code' => substr($item->requirement_key, 9), 'label' => $item->label, 'required' => (bool) $item->is_required])->values()->all();

        return ['country_code' => $countryCode, 'country_name' => $country?->name, 'eligible' => (bool) $country?->capabilities?->kyc_enabled, 'required_fields' => $requiredFields, 'document_types' => $documentTypes, 'available' => (bool) $country?->capabilities?->kyc_enabled && $requiredFields !== [] && $documentTypes !== []];
    }

    private function assertKycAvailable(?string $countryCode, CountryEligibilityService $eligibility): void
    {
        abort_unless($countryCode && $eligibility->capability($countryCode, 'kyc_enabled'), 403, 'KYC is not available in your country.');
    }

    private function assertDraftEditable(KycCase $case): void
    {
        abort_if(in_array($case->status, ['submitted', 'under_review', 'approved'], true), 422, 'This KYC application is locked for authorized review.');
    }

    private function recordStatus(KycCase $case, string $from, string $to, string $actorId, string $reason): void
    {
        if ($from === $to) return;
        $case->statusEvents()->create(['actor_id' => $actorId, 'from_status' => $from, 'to_status' => $to, 'reason' => $reason, 'metadata' => ['sandbox_only' => true]]);
    }
}
