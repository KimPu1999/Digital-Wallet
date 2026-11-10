<?php
namespace App\Domain\AML;

use App\Models\PaymentIntent;
use App\Models\Transfer;
use App\Models\FxConversion;
use App\Models\RiskRule;
use App\Models\RiskAlert;

final class TransactionMonitoringService
{
    public function __construct(private RiskRuleEngine $engine) {}
    public function monitor(object $transaction, array $context = []): array
    {
        $subjectType = get_class($transaction); $subjectId = (string) $transaction->getKey();
        $ruleKey = match (true) { $transaction instanceof Transfer => 'transaction_velocity', $transaction instanceof PaymentIntent => 'payment_activity', $transaction instanceof FxConversion => 'fx_activity', default => 'transaction_velocity' };
        $evaluation = $this->engine->evaluate($subjectType, $subjectId, $ruleKey, $context + ['transaction_id' => $subjectId]);
        return ['evaluation' => $evaluation, 'alert' => RiskAlert::where('risk_evaluation_id', $evaluation->id)->first(), 'sandbox_only' => true];
    }
}
