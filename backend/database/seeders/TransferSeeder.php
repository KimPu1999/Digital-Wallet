<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder; use Illuminate\Support\Facades\DB; use Illuminate\Support\Str;
class TransferSeeder extends Seeder { public function run(): void { $now=now(); foreach(['USD','EUR','GBP','SGD','THB','MMK'] as $currency) DB::table('transfer_limits')->updateOrInsert(['scope_type'=>'customer','scope_key'=>'default','currency_code'=>$currency],['id'=>DB::table('transfer_limits')->where(['scope_type'=>'customer','scope_key'=>'default','currency_code'=>$currency])->value('id') ?: (string)Str::uuid(),'per_transaction_minor'=>100000000,'daily_minor'=>250000000,'monthly_minor'=>1000000000,'is_enabled'=>true,'effective_from'=>today(),'metadata'=>json_encode(['sandbox_only'=>true,'regulatory_limit'=>false]),'created_at'=>$now,'updated_at'=>$now]); } }
