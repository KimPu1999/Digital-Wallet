<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\Role;
use App\Models\User;

Artisan::command('inspire', function (): void {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('test {--filter=}', function (): int {
    putenv('APP_ENV=testing');
    putenv('DB_CONNECTION=sqlite');
    putenv('DB_DATABASE=:memory:');
    putenv('CACHE_STORE=array');
    putenv('SESSION_DRIVER=array');
    putenv('QUEUE_CONNECTION=sync');
    $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg(base_path('vendor/bin/phpunit'));
    if ($filter = $this->option('filter')) {
        $command .= ' --filter '.escapeshellarg($filter);
    }
    passthru($command, $status);
    return $status;
})->purpose('Run the PHPUnit test suite');

Artisan::command('admin:provision {email} {name} {--role=platform_super_admin}', function (): int {
    $roleName = (string) $this->option('role');
    if (!in_array($roleName, ['platform_super_admin', 'compliance_admin', 'support_agent'], true)) { $this->error('Unsupported staff role.'); return 1; }
    $temporaryPassword = Str::random(32);
    $user = User::updateOrCreate(['email' => $this->argument('email')], ['name' => $this->argument('name'), 'password' => Hash::make($temporaryPassword), 'account_status' => 'active', 'user_type' => 'staff', 'is_active' => true]);
    $role = Role::firstOrCreate(['name' => $roleName], ['guard_name' => 'web']);
    DB::table('role_assignments')->updateOrInsert(['user_id'=>$user->id,'role_id'=>$role->id],['id'=>(string) Str::uuid(),'created_at'=>now(),'updated_at'=>now()]);
    $this->info('Staff account provisioned. Deliver the one-time password through an approved secure channel:');
    $this->line($temporaryPassword);
    return 0;
})->purpose('Provision a staff account; never exposed as a public registration flow');

Artisan::command('ledger:seed-accounts {currency?}', function (): int {
    $currencies = $this->argument('currency') ? [strtoupper((string) $this->argument('currency'))] : ['USD', 'EUR', 'GBP', 'SGD', 'THB', 'MMK'];
    $chart = app(\App\Services\ChartOfAccountsService::class);
    foreach ($currencies as $currency) {
        $chart->provisionSystemAccount('system.settlement.'.strtolower($currency), 'Platform settlement '.$currency, 'asset', 'debit', $currency);
        $chart->provisionSystemAccount('system.fees.'.strtolower($currency), 'Fee revenue '.$currency, 'revenue', 'credit', $currency);
        $chart->provisionSystemAccount('system.suspense.'.strtolower($currency), 'Restricted suspense '.$currency, 'asset', 'debit', $currency, true);
    }
    $this->info('System chart-of-accounts records are ready. No balances were created.');
    return 0;
})->purpose('Provision inactive-by-default system ledger accounts for sandbox operations');

Artisan::command('ledger:reconcile', function (): int {
    $run = app(\App\Services\ReconciliationService::class)->run();
    $this->info('Reconciliation '.$run->status.' with '.($run->summary['exceptions'] ?? 0).' exception(s).');
    return $run->status === 'completed' ? 0 : 1;
})->purpose('Run a read-only ledger and projection reconciliation');
