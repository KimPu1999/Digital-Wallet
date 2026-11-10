<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PersonalSettingsTest extends TestCase
{
    private User $customer;
    private User $other;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
        $this->customer = $this->makeUser('settings-customer@example.com');
        $this->other = $this->makeUser('settings-other@example.com');
        DB::table('countries')->insert(['code' => 'MM', 'name' => 'Myanmar', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('country_capabilities')->insert(['country_code' => 'MM', 'registration_enabled' => true, 'kyc_enabled' => false, 'wallet_enabled' => true, 'deposits_enabled' => false, 'withdrawals_enabled' => false, 'transfers_enabled' => false, 'fx_enabled' => false, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('currencies')->insert(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimals' => 2, 'display_order' => 1, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function test_customer_can_read_and_update_owned_profile_and_preferences(): void
    {
        $this->actingAs($this->customer);
        $this->patchJson('/api/v1/me/profile', ['name' => 'Updated Customer', 'display_name' => 'Updated'])->assertOk()->assertJsonPath('data.name', 'Updated Customer');
        $this->postJson('/api/v1/wallets', ['currency_code' => 'USD'])->assertCreated();
        $this->patchJson('/api/v1/me/preferences', ['locale' => 'my', 'theme' => 'dark', 'default_currency' => 'USD', 'balance_visible' => false])->assertOk()->assertJsonPath('data.locale', 'my')->assertJsonPath('data.balance_visible', false);
        $this->assertDatabaseHas('customer_profiles', ['user_id' => $this->customer->id, 'display_name' => 'Updated']);
    }

    public function test_preferences_reject_wallet_not_owned_by_customer(): void
    {
        $walletId = $this->actingAs($this->customer)->postJson('/api/v1/wallets', ['currency_code' => 'USD'])->json('data.id');
        $this->actingAs($this->other)->patchJson('/api/v1/me/preferences', ['preferred_wallet_id' => $walletId])->assertUnprocessable();
    }

    public function test_notification_preferences_are_owned_and_created_for_customer(): void
    {
        $this->actingAs($this->customer)->patchJson('/api/v1/me/notifications/preferences', ['marketing_email' => false])->assertOk()->assertJsonPath('data.marketing_email', false);
        $this->actingAs($this->other)->getJson('/api/v1/me/notifications/preferences')->assertOk()->assertJsonPath('data.user_id', $this->other->id);
        $this->assertDatabaseHas('notification_preferences', ['user_id' => $this->customer->id, 'marketing_email' => false]);
        $this->assertDatabaseHas('notification_preferences', ['user_id' => $this->other->id]);
    }

    public function test_customer_privacy_requests_are_isolated(): void
    {
        $created = $this->actingAs($this->customer)->postJson('/api/v1/me/privacy/requests', ['request_type' => 'access', 'scope_summary' => 'My account data']);
        $created->assertCreated();
        $this->actingAs($this->customer)->getJson('/api/v1/me/privacy/requests')->assertOk()->assertJsonPath('data.data.0.subject_user_id', $this->customer->id);
        $otherResponse = $this->actingAs($this->other)->getJson('/api/v1/me/privacy/requests')->assertOk();
        $this->assertSame([], $otherResponse->json('data.data'));
    }

    public function test_password_change_requires_current_password_and_revokes_sessions(): void
    {
        DB::table('active_sessions')->insert(['id' => 'session-settings-1', 'user_id' => $this->customer->id, 'session_hash' => hash('sha256', 'settings-session'), 'last_seen_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs($this->customer)->postJson('/api/v1/me/password', ['current_password' => 'password-password', 'password' => 'NewSecure!Password26', 'password_confirmation' => 'NewSecure!Password26'])->assertOk();
        $this->assertDatabaseMissing('active_sessions', ['id' => 'session-settings-1']);
    }

    private function makeUser(string $email): User
    {
        $user = User::create(['name' => 'Settings Customer', 'email' => $email, 'password' => 'password-password', 'country_code' => 'MM', 'account_status' => 'active', 'user_type' => 'customer', 'email_verified_at' => now()]);
        $user->forceFill(['is_active' => true])->save();
        return $user;
    }
}
