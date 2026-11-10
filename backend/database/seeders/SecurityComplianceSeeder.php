<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SecurityComplianceSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            ['rule_key' => 'transaction_velocity', 'name' => 'Transaction velocity review', 'category' => 'aml', 'description' => 'Reviews unusually frequent sandbox activity. Configure per jurisdiction before production use.'],
            ['rule_key' => 'payment_activity', 'name' => 'Payment activity review', 'category' => 'aml', 'description' => 'Reviews payment activity and required screening state.'],
            ['rule_key' => 'fx_activity', 'name' => 'FX activity review', 'category' => 'aml', 'description' => 'Reviews sandbox FX activity without applying regulatory thresholds.'],
            ['rule_key' => 'failed_auth_velocity', 'name' => 'Failed authentication velocity', 'category' => 'fraud', 'description' => 'Flags repeated failed authentication attempts for analyst review.'],
        ];
        foreach ($rules as $rule) {
            $id = DB::table('risk_rules')->where('rule_key', $rule['rule_key'])->value('id') ?: (string) Str::uuid();
            DB::table('risk_rules')->updateOrInsert(['rule_key' => $rule['rule_key']], $rule + ['id' => $id, 'default_decision' => 'review', 'enabled' => true, 'created_at' => now(), 'updated_at' => now()]);
            $version = DB::table('risk_rule_versions')->where(['risk_rule_id' => $id, 'version' => 1])->value('id');
            if (!$version) DB::table('risk_rule_versions')->insert(['id' => (string) Str::uuid(), 'risk_rule_id' => $id, 'version' => 1, 'configuration' => json_encode(['velocity_limit' => 20, 'failed_attempt_limit' => 5, 'jurisdictional_thresholds_required' => true]), 'status' => 'published', 'published_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('security_policy_versions')->updateOrInsert(['policy_key' => 'monitoring_default'], ['id' => DB::table('security_policy_versions')->where(['policy_key' => 'monitoring_default', 'version' => 1])->value('id') ?: (string) Str::uuid(), 'version' => 1, 'status' => 'published', 'configuration' => json_encode(['sandbox_only' => true, 'required_screening' => true, 'manual_review_for_matches' => true]), 'published_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
    }
}
