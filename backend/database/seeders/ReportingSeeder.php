<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
class ReportingSeeder extends Seeder { public function run(): void { foreach (['overview','trial-balance','general-ledger','profit-loss','balance-sheet','wallets','transfers','fx','payments','refunds','settlements','reconciliation','merchant-overview'] as $type) DB::table('report_definitions')->updateOrInsert(['code'=>'platform.'.$type,'version'=>1], ['id'=>DB::table('report_definitions')->where('code','platform.'.$type)->where('version',1)->value('id') ?: (string) Str::uuid(), 'name'=>ucwords(str_replace('-', ' ', $type)), 'report_type'=>$type, 'scope'=>'platform', 'definition'=>json_encode(['source'=>'posted ledger and verified sandbox records']), 'status'=>'active', 'created_at'=>now(), 'updated_at'=>now()]); } }
