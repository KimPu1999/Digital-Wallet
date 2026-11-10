<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder; use Illuminate\Support\Facades\DB; use Illuminate\Support\Str;
class FxSeeder extends Seeder
{
    public function run(): void
    {
        $now=now(); $providerId=(string)Str::uuid(); DB::table('fx_rate_providers')->updateOrInsert(['code'=>'sandbox-mock'],['id'=>DB::table('fx_rate_providers')->where('code','sandbox-mock')->value('id') ?: $providerId,'name'=>'Sandbox deterministic rate provider','provider_type'=>'sandbox','status'=>'active','timeout_ms'=>1000,'stale_after_seconds'=>120,'metadata'=>json_encode(['live_provider'=>false]),'created_at'=>$now,'updated_at'=>$now]);
        $pairs=[['USD','EUR'],['EUR','USD'],['USD','GBP'],['GBP','USD'],['USD','SGD'],['SGD','USD'],['USD','THB'],['THB','USD'],['USD','MMK'],['MMK','USD'],['EUR','GBP'],['GBP','EUR']];
        foreach($pairs as [$base,$quote]) { $pairId=DB::table('fx_pairs')->where(['base_currency_code'=>$base,'quote_currency_code'=>$quote])->value('id') ?: (string)Str::uuid(); DB::table('fx_pairs')->updateOrInsert(['base_currency_code'=>$base,'quote_currency_code'=>$quote],['id'=>$pairId,'is_enabled'=>true,'provider_available'=>true,'min_amount_minor'=>1,'max_amount_minor'=>100000000000,'quote_expiry_seconds'=>60,'country_eligibility'=>json_encode([]),'risk_restrictions'=>json_encode(['sandbox_only'=>true]),'created_at'=>$now,'updated_at'=>$now]); DB::table('fx_fee_rules')->updateOrInsert(['pair_id'=>$pairId],['id'=>DB::table('fx_fee_rules')->where('pair_id',$pairId)->value('id') ?: (string)Str::uuid(),'fixed_fee_minor'=>0,'percentage_bps'=>50,'provider_spread_bps'=>25,'platform_markup_bps'=>25,'minimum_fee_minor'=>0,'maximum_fee_minor'=>null,'is_enabled'=>true,'created_at'=>$now,'updated_at'=>$now]); }
    }
}
