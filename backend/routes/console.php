<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schedule;

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
    $this->error('admin:provision is retired because it could overwrite accounts and expose credentials. Use platform:create-super-admin.');
    return 1;
})->purpose('Retired legacy provisioning command; use the interactive secure bootstrap command');

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

Artisan::command('fx:reconcile', function (): int {
    $summary = app(\App\Services\FxReconciliationService::class)->run();
    $this->info('FX reconciliation '.$summary['status'].' with '.$summary['exceptions'].' exception(s).');
    return $summary['exceptions'] === 0 ? 0 : 1;
})->purpose('Check completed sandbox FX conversions against their ledger legs');

Schedule::command('ledger:reconcile')->hourly()->withoutOverlapping(20)->onOneServer();
Schedule::command('fx:reconcile')->hourlyAt(15)->withoutOverlapping(20)->onOneServer();
