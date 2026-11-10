<?php
namespace App\Services;
use App\Domain\Fx\FxProviderRate; use App\Domain\Fx\FxRateProviderInterface; use App\Models\FxPair; use Illuminate\Validation\ValidationException;
final class MockFxRateProvider implements FxRateProviderInterface
{
    private array $rates=['USD/EUR'=>'0.920000000000000000','EUR/USD'=>'1.086956521739130435','USD/GBP'=>'0.790000000000000000','GBP/USD'=>'1.265822784810126582','USD/SGD'=>'1.340000000000000000','SGD/USD'=>'0.746268656716417910','USD/THB'=>'35.100000000000000000','THB/USD'=>'0.028490028490028490','USD/MMK'=>'4200.000000000000000000','MMK/USD'=>'0.000238095238095238','EUR/GBP'=>'0.858000000000000000','GBP/EUR'=>'1.165501165501165501'];
    public function code(): string { return 'sandbox-mock'; }
    public function rate(FxPair $pair): FxProviderRate { $key="{$pair->base_currency_code}/{$pair->quote_currency_code}"; $rate=$this->rates[$key] ?? null; if(!$rate) throw ValidationException::withMessages(['pair'=>'No sandbox rate is configured for this currency pair.']); return new FxProviderRate($rate, 'sandbox-'.$key, min((int)$pair->quote_expiry_seconds, 60), ['sandbox'=>true]); }
}
