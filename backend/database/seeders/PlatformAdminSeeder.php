<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlatformAdminSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['platform_super_admin', 'compliance_admin', 'finance_operations', 'risk_analyst', 'support_agent', 'read_only_auditor'];
        $permissions = ['dashboard.read', 'users.read', 'users.restrict', 'merchants.read', 'compliance.read', 'compliance.decide', 'financial.read', 'reconciliation.read', 'risk.read', 'risk.manage', 'security.read', 'support.read', 'support.manage', 'approvals.request', 'approvals.decide', 'audit.read', 'settings.read', 'settings.manage', 'system.health'];
        foreach ($roles as $name) DB::table('platform_roles')->updateOrInsert(['name' => $name], ['id' => DB::table('platform_roles')->where('name', $name)->value('id') ?: (string) Str::uuid(), 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        foreach ($permissions as $name) DB::table('platform_permissions')->updateOrInsert(['name' => $name], ['id' => DB::table('platform_permissions')->where('name', $name)->value('id') ?: (string) Str::uuid(), 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()]);
        $all = DB::table('platform_permissions')->pluck('id', 'name');
        $matrix = [
            'platform_super_admin' => $permissions,
            'compliance_admin' => ['dashboard.read', 'users.read', 'merchants.read', 'compliance.read', 'compliance.decide', 'risk.read', 'security.read', 'audit.read', 'system.health'],
            'finance_operations' => ['dashboard.read', 'financial.read', 'reconciliation.read', 'audit.read', 'system.health'],
            'risk_analyst' => ['dashboard.read', 'users.read', 'merchants.read', 'risk.read', 'risk.manage', 'security.read', 'audit.read', 'system.health'],
            'support_agent' => ['dashboard.read', 'users.read', 'merchants.read', 'compliance.read', 'support.read', 'support.manage', 'system.health'],
            'read_only_auditor' => ['dashboard.read', 'financial.read', 'compliance.read', 'risk.read', 'support.read', 'audit.read', 'system.health'],
        ];
        foreach ($matrix as $role => $names) {
            $roleId = DB::table('platform_roles')->where('name', $role)->value('id');
            foreach ($names as $permission) DB::table('platform_role_permissions')->updateOrInsert(['platform_role_id' => $roleId, 'platform_permission_id' => $all[$permission]], ['created_at' => now(), 'updated_at' => now()]);
        }
        foreach (['merchant_payments_enabled', 'provider_live_operations_enabled', 'customer_money_movement_enabled'] as $flag) DB::table('platform_feature_flags')->updateOrInsert(['flag_key' => $flag], ['id' => DB::table('platform_feature_flags')->where('flag_key', $flag)->value('id') ?: (string) Str::uuid(), 'enabled' => false, 'environment' => 'sandbox', 'updated_at' => now(), 'created_at' => now()]);
    }
}
