<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder; use Illuminate\Support\Facades\DB; use Illuminate\Support\Str;
class DatabaseSeeder extends Seeder {
    public function run(): void {
        DB::table('countries')->upsert([['code'=>'MM','name'=>'Myanmar','is_active'=>true,'created_at'=>now(),'updated_at'=>now()],['code'=>'SG','name'=>'Singapore','is_active'=>true,'created_at'=>now(),'updated_at'=>now()],['code'=>'US','name'=>'United States','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]],['code'],['name','is_active','updated_at']);
        foreach(['MM','SG','US'] as $code) DB::table('country_capabilities')->updateOrInsert(['country_code'=>$code],['registration_enabled'=>true,'kyc_enabled'=>false,'wallet_enabled'=>false,'deposits_enabled'=>false,'withdrawals_enabled'=>false,'transfers_enabled'=>false,'fx_enabled'=>false,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('currencies')->upsert([['code'=>'USD','name'=>'US Dollar','symbol'=>'$','decimals'=>2,'display_order'=>1,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()],['code'=>'EUR','name'=>'Euro','symbol'=>'€','decimals'=>2,'display_order'=>2,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()],['code'=>'GBP','name'=>'British Pound','symbol'=>'£','decimals'=>2,'display_order'=>3,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()],['code'=>'SGD','name'=>'Singapore Dollar','symbol'=>'S$','decimals'=>2,'display_order'=>4,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()],['code'=>'THB','name'=>'Thai Baht','symbol'=>'฿','decimals'=>2,'display_order'=>5,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()],['code'=>'MMK','name'=>'Myanmar Kyat','symbol'=>'K','decimals'=>0,'display_order'=>6,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]],['code'],['name','symbol','decimals','display_order','is_active','updated_at']);
        foreach(['platform_super_admin','compliance_admin','support_agent','merchant_owner','merchant_manager','customer'] as $role) DB::table('roles')->updateOrInsert(['name'=>$role],['id'=>(string) Str::uuid(),'guard_name'=>'web','created_at'=>now(),'updated_at'=>now()]);
        $this->call(FxSeeder::class);
        $this->call(TransferSeeder::class);
        $this->call(PaymentSeeder::class);
        $this->call(PlatformAdminSeeder::class);
        $this->call(SecurityComplianceSeeder::class);
    }
}
