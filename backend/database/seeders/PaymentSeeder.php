<?php

namespace Database\Seeders;

use App\Models\PaymentProvider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $currencies = ['USD', 'EUR', 'GBP', 'SGD', 'THB', 'MMK'];
        $countries = ['MM', 'SG', 'US'];
        $mock = PaymentProvider::updateOrCreate(['code' => 'sandbox-mock'], [
            'name' => 'Sandbox Mock Provider',
            'provider_type' => 'mock',
            'environment' => 'sandbox',
            'status' => 'active',
            'enabled' => true,
            'supported_countries' => $countries,
            'supported_currencies' => $currencies,
            'supported_payment_methods' => ['card', 'bank_transfer'],
            'supported_operations' => ['deposit', 'withdrawal'],
            'health_status' => 'healthy',
            'timeout_ms' => 1000,
            'metadata' => ['sandbox_only' => true],
        ]);
        $mock->accounts()->updateOrCreate(['account_key' => 'sandbox-default', 'environment' => 'sandbox'], ['status' => 'active', 'metadata' => ['sandbox_only' => true]]);
        foreach ($currencies as $currency) {
            foreach (['deposit', 'withdrawal'] as $operation) {
                $mock->capabilities()->updateOrCreate(['operation' => $operation, 'country_code' => null, 'currency_code' => $currency, 'payment_method' => null], ['is_supported' => true, 'metadata' => ['sandbox_only' => true]]);
            }
        }
        $mock->routingRules()->updateOrCreate(['operation' => 'deposit', 'country_code' => null, 'currency_code' => null, 'payment_method' => null], ['priority' => 1, 'is_enabled' => true, 'metadata' => ['sandbox_only' => true]]);
        $mock->routingRules()->updateOrCreate(['operation' => 'withdrawal', 'country_code' => null, 'currency_code' => null, 'payment_method' => null], ['priority' => 1, 'is_enabled' => true, 'metadata' => ['sandbox_only' => true]]);

        foreach ([
            ['code' => 'stripe', 'name' => 'Stripe', 'provider_type' => 'card_processor'],
            ['code' => 'wise', 'name' => 'Wise Platform', 'provider_type' => 'payout_provider'],
            ['code' => 'airwallex', 'name' => 'Airwallex', 'provider_type' => 'payment_platform'],
        ] as $definition) {
            PaymentProvider::updateOrCreate(['code' => $definition['code']], $definition + ['environment' => 'production', 'status' => 'disabled', 'enabled' => false, 'health_status' => 'unknown', 'metadata' => ['sandbox_only' => true, 'credentials_configured' => false]]);
        }
    }
}
