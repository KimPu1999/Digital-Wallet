<?php
namespace App\Domain\Compliance;

use App\Domain\Screening\ScreeningProviderRegistry;
use App\Models\ScreeningMatch;
use App\Models\ScreeningRequest;
use Illuminate\Support\Str;

final class SanctionsScreeningService
{
    public function __construct(private ScreeningProviderRegistry $registry) {}
    public function screen(string $subjectType, string $subjectId, array $subject, string $provider = 'mock_sandbox'): ScreeningRequest
    {
        $key = hash('sha256', $subjectType.'|'.$subjectId.'|'.json_encode($subject, JSON_THROW_ON_ERROR));
        $existing = ScreeningRequest::where('request_key', $key)->first(); if ($existing) return $existing->load('matches');
        $request = ScreeningRequest::create(['subject_type' => $subjectType, 'subject_id' => $subjectId, 'provider' => $provider, 'status' => 'pending', 'request_key' => $key, 'requested_at' => now(), 'metadata' => ['sandbox_only' => true, 'mock_result' => true]]);
        try { $result = $this->registry->get($provider)->screen($subject); $request->update(['status' => $result['status'], 'completed_at' => now()]); foreach ($result['matches'] as $match) ScreeningMatch::create($match + ['screening_request_id' => $request->id, 'subject_facts' => ['sandbox_only' => true]]); }
        catch (\Throwable $e) { $request->update(['status' => 'failed', 'completed_at' => now(), 'metadata' => ['sandbox_only' => true, 'failure_class' => class_basename($e)]]); }
        return $request->refresh()->load('matches');
    }
}
