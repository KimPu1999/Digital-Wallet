<?php
namespace App\Domain\AML;

use App\Models\RiskRule;
use App\Models\RiskRuleVersion;
use App\Models\RiskEvaluation;
use App\Models\RiskAlert;
use Illuminate\Support\Str;

final class RiskRuleEngine
{
    public function evaluate(string $subjectType, string $subjectId, string $ruleKey, array $context = []): RiskEvaluation
    {
        $rule = RiskRule::where('rule_key', $ruleKey)->firstOrFail();
        $version = RiskRuleVersion::where('risk_rule_id', $rule->id)->where('status', 'published')->latest('version')->first();
        $key = hash('sha256', $subjectType.'|'.$subjectId.'|'.$ruleKey.'|'.json_encode($context, JSON_THROW_ON_ERROR));
        $existing = RiskEvaluation::where('evaluation_key', $key)->first();
        if ($existing) return $existing;
        $decision = 'allow';
        $failure = null;
        if (!$rule->enabled || !$version) { $decision = 'review'; $failure = 'Required risk rule configuration is unavailable.'; }
        elseif (($context['screening_required'] ?? false) && ($context['screening_status'] ?? null) !== 'no_match') { $decision = 'review'; $failure = 'Required screening did not complete with a no-match result.'; }
        elseif (($context['screening_status'] ?? null) === 'potential_match') { $decision = 'review'; }
        elseif (($context['failed_attempts'] ?? 0) >= (int) ($version->configuration['failed_attempt_limit'] ?? 5)) { $decision = 'review'; }
        elseif (($context['velocity_count'] ?? 0) > (int) ($version->configuration['velocity_limit'] ?? 20)) { $decision = 'review'; }
        $evaluation = RiskEvaluation::create(['subject_type' => $subjectType, 'subject_id' => $subjectId, 'risk_rule_version_id' => $version?->id, 'decision' => $decision, 'evaluation_key' => $key, 'evidence' => ['context' => $context, 'sandbox_only' => true], 'failure_reason' => $failure]);
        if ($decision !== 'allow') RiskAlert::firstOrCreate(['deduplication_key' => $key], ['risk_evaluation_id' => $evaluation->id, 'subject_type' => $subjectType, 'subject_id' => $subjectId, 'category' => $rule->category, 'severity' => $decision === 'reject' ? 'high' : 'medium', 'status' => 'open', 'reason' => $failure ?: $rule->name, 'evidence' => ['evaluation_id' => $evaluation->id, 'sandbox_only' => true]]);
        return $evaluation;
    }
}
