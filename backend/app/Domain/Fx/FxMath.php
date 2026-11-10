<?php
namespace App\Domain\Fx;
use Illuminate\Validation\ValidationException;
final class FxMath
{
    private static function ready(): void { if (!function_exists('bcadd')) throw ValidationException::withMessages(['fx'=>'BCMath is required for sandbox FX calculations.']); }
    public static function rateAfterBps(string $rate, int $bps): string { self::ready(); if ($bps < 0 || $bps > 10000) throw ValidationException::withMessages(['fx'=>'Invalid FX spread.']); return bcdiv(bcmul($rate, (string) (10000 - $bps), 18), '10000', 18); }
    public static function target(int $amountMinor, string $rate): int { self::ready(); if ($amountMinor < 1) throw ValidationException::withMessages(['source_amount_minor'=>'Amount must be positive.']); $value = bcdiv(bcmul((string)$amountMinor, $rate, 18), '1', 0); if (bccomp($value, (string)PHP_INT_MAX, 0) > 0) throw ValidationException::withMessages(['source_amount_minor'=>'Calculated amount exceeds integer limits.']); return (int)$value; }
    public static function fee(int $amountMinor, int $fixed, int $percentageBps, int $minimum, ?int $maximum): int { self::ready(); $percentage = bcdiv(bcmul((string)$amountMinor, (string)$percentageBps, 0), '10000', 0); $fee = bcadd((string)$fixed, $percentage, 0); if (bccomp($fee, (string)$minimum, 0) < 0) $fee=(string)$minimum; if ($maximum !== null && bccomp($fee, (string)$maximum, 0) > 0) $fee=(string)$maximum; if (bccomp($fee, (string)PHP_INT_MAX, 0) > 0) throw ValidationException::withMessages(['fee'=>'Calculated fee exceeds integer limits.']); return (int)$fee; }
    public static function add(int ...$values): int { self::ready(); $total='0'; foreach($values as $value) { if($value<0) throw ValidationException::withMessages(['amount'=>'Amount cannot be negative.']); $total=bcadd($total,(string)$value,0); } if(bccomp($total,(string)PHP_INT_MAX,0)>0) throw ValidationException::withMessages(['amount'=>'Amount exceeds integer limits.']); return (int)$total; }
}
