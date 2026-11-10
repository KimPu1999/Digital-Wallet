<?php
namespace App\Services;
use App\Models\TransferLimit; use App\Models\TransferLimitUsage; use App\Models\User; use Illuminate\Support\Facades\DB; use Illuminate\Validation\ValidationException;
final class TransferLimitService
{
    public function reserve(User $user, string $currency, int $amount): TransferLimitUsage
    {
        $limit=TransferLimit::where('scope_type','customer')->where('scope_key','default')->where('currency_code',$currency)->where('is_enabled',true)->whereDate('effective_from','<=',today())->where(fn($q)=>$q->whereNull('effective_until')->orWhereDate('effective_until','>=',today()))->first();
        if(!$limit) return new TransferLimitUsage(['daily_minor'=>0,'monthly_minor'=>0]);
        return DB::transaction(function() use($user,$currency,$amount,$limit): TransferLimitUsage {
            $usage=TransferLimitUsage::firstOrCreate(['user_id'=>$user->id,'limit_id'=>$limit->id,'currency_code'=>$currency,'usage_date'=>today()],['daily_minor'=>0,'monthly_minor'=>0,'version'=>0]);
            $usage=TransferLimitUsage::whereKey($usage->id)->lockForUpdate()->firstOrFail(); $monthly=$usage->monthly_minor; $daily=$usage->daily_minor;
            if($limit->per_transaction_minor!==null && $amount>(int)$limit->per_transaction_minor) throw ValidationException::withMessages(['limit'=>'This transfer exceeds the per-transaction sandbox limit.']);
            if($limit->daily_minor!==null && $daily+$amount>(int)$limit->daily_minor) throw ValidationException::withMessages(['limit'=>'This transfer exceeds the daily sandbox limit.']);
            if($limit->monthly_minor!==null && $monthly+$amount>(int)$limit->monthly_minor) throw ValidationException::withMessages(['limit'=>'This transfer exceeds the monthly sandbox limit.']);
            $usage->update(['daily_minor'=>$daily+$amount,'monthly_minor'=>$monthly+$amount,'version'=>$usage->version+1]); return $usage->refresh();
        },3);
    }
    public function release(User $user, string $currency, int $amount): void { $limit=TransferLimit::where('scope_type','customer')->where('scope_key','default')->where('currency_code',$currency)->first(); if(!$limit) return; DB::transaction(function() use($user,$currency,$amount,$limit): void { $usage=TransferLimitUsage::where(['user_id'=>$user->id,'limit_id'=>$limit->id,'currency_code'=>$currency])->lockForUpdate()->latest('usage_date')->first(); if($usage) $usage->update(['daily_minor'=>max(0,$usage->daily_minor-$amount),'monthly_minor'=>max(0,$usage->monthly_minor-$amount),'version'=>$usage->version+1]); }); }
}
