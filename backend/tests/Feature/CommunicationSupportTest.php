<?php

namespace Tests\Feature;

use App\Domain\Notifications\NotificationEventService;
use App\Domain\Support\SupportAssignmentService;
use App\Domain\Support\SupportTicketService;
use App\Models\MfaMethod;
use App\Models\Merchant;
use App\Models\MerchantMembership;
use App\Models\Notification;
use App\Models\PlatformAdminMembership;
use App\Models\PlatformRole;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\AdminStepUpService;
use Database\Seeders\CommunicationSeeder;
use Database\Seeders\PlatformAdminSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CommunicationSupportTest extends TestCase
{
    private User $customer;
    private User $other;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--force' => true]);
        (new PlatformAdminSeeder())->run();
        (new CommunicationSeeder())->run();
        $this->customer = $this->makeUser('notify-customer@example.com');
        $this->other = $this->makeUser('notify-other@example.com');
        $this->admin = $this->makeAdmin();
    }

    public function test_notification_event_is_deduplicated_and_owned(): void
    {
        $first = app(NotificationEventService::class)->emit($this->customer, 'transfer|demo-1|completed', 'transfer.completed', 'support_ticket_update', ['reference' => 'TRF-1', 'status' => 'completed']);
        $second = app(NotificationEventService::class)->emit($this->customer, 'transfer|demo-1|completed', 'transfer.completed', 'support_ticket_update', ['reference' => 'TRF-1', 'status' => 'completed']);
        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, Notification::where('notifiable_id', $this->customer->id)->count());
        $this->actingAs($this->customer)->getJson('/api/v1/notifications')->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($this->other)->patchJson('/api/v1/notifications/'.$first->id.'/read')->assertNotFound();
    }

    public function test_notification_preferences_and_read_state_work(): void
    {
        $notification = app(NotificationEventService::class)->emit($this->customer, 'security|demo-1', 'security.alert', 'security_alert', [], 'security', true);
        $this->actingAs($this->customer)->patchJson('/api/v1/notification-preferences', ['marketing_email' => false, 'support_push' => true])->assertOk()->assertJsonPath('data.marketing_email', false);
        $read = $this->actingAs($this->customer)->patchJson('/api/v1/notifications/'.$notification->id.'/read')->assertOk();
        $this->assertNotNull($read->json('data.read_at'));
        $this->actingAs($this->customer)->getJson('/api/v1/notifications/unread-count')->assertOk()->assertJsonPath('data.unread', 0);
    }

    public function test_support_ticket_ownership_duplicate_messages_and_internal_note_privacy(): void
    {
        $created = $this->actingAs($this->customer)->postJson('/api/v1/support/tickets', ['category' => 'general', 'subject' => 'Sandbox help', 'message' => 'Please help', 'client_message_key' => 'first-message']);
        $created->assertCreated();
        $ticketId = $created->json('data.id');
        $this->actingAs($this->customer)->postJson('/api/v1/support/tickets/'.$ticketId.'/messages', ['body' => 'Duplicate-safe reply', 'client_message_key' => 'reply-1'])->assertCreated();
        $this->actingAs($this->customer)->postJson('/api/v1/support/tickets/'.$ticketId.'/messages', ['body' => 'Duplicate-safe reply', 'client_message_key' => 'reply-1'])->assertCreated();
        $this->assertSame(2, DB::table('support_messages')->where('support_ticket_id', $ticketId)->count());
        $this->actingAs($this->other)->getJson('/api/v1/support/tickets/'.$ticketId)->assertNotFound();
        $this->withSession(['platform_admin_authenticated_at' => now()->toIso8601String()])->actingAs($this->admin)->postJson('/api/v1/platform/support/tickets/'.$ticketId.'/reply', ['body' => 'Internal analyst note', 'visibility' => 'internal'])->assertCreated();
        $this->actingAs($this->customer)->getJson('/api/v1/support/tickets/'.$ticketId)->assertJsonMissing(['body' => 'Internal analyst note']);
    }

    public function test_support_assignment_and_valid_transition_require_platform_permission(): void
    {
        $ticket = SupportTicket::create(['user_id' => $this->customer->id, 'created_by' => $this->customer->id, 'ticket_reference' => 'SUP-ASSIGN', 'category' => 'account_access', 'subject' => 'Access', 'priority' => 'high', 'status' => 'open']);
        $agent = $this->makeUser('agent@example.com', 'staff');
        $this->withSession(['platform_admin_authenticated_at' => now()->toIso8601String()])->actingAs($this->admin)->postJson('/api/v1/platform/support/tickets/'.$ticket->id.'/assign', ['agent_id' => $agent->id])->assertOk()->assertJsonPath('data.assigned_to', $agent->id);
        $this->withSession(['platform_admin_authenticated_at' => now()->toIso8601String()])->actingAs($this->admin)->postJson('/api/v1/platform/support/tickets/'.$ticket->id.'/transition', ['status' => 'in_progress', 'notes' => 'Investigation started'])->assertOk()->assertJsonPath('data.status', 'in_progress');
        $this->actingAs($this->other)->getJson('/api/v1/platform/support/tickets')->assertForbidden();
    }

    public function test_merchant_support_isolated_and_unsafe_attachment_is_rejected(): void
    {
        $merchantA = Merchant::create(['legal_name' => 'A Ltd', 'display_name' => 'A', 'status' => 'active']);
        $merchantB = Merchant::create(['legal_name' => 'B Ltd', 'display_name' => 'B', 'status' => 'active']);
        MerchantMembership::create(['merchant_id' => $merchantA->id, 'user_id' => $this->customer->id, 'role' => 'owner']);
        $this->actingAs($this->customer)->postJson('/api/v1/merchant/support/tickets', ['merchant_id' => $merchantA->id, 'category' => 'api', 'subject' => 'A issue', 'message' => 'A only'])->assertCreated();
        $this->actingAs($this->customer)->getJson('/api/v1/merchant/support/tickets?merchant_id='.$merchantB->id)->assertStatus(422);
        $ticket = SupportTicket::where('merchant_id', $merchantA->id)->firstOrFail();
        $this->actingAs($this->customer)->withHeader('Accept', 'application/json')->post('/api/v1/support/tickets/'.$ticket->id.'/attachments', ['attachment' => UploadedFile::fake()->create('payload.exe', 10, 'application/octet-stream')])->assertStatus(422);
    }

    private function makeUser(string $email, string $type = 'customer'): User
    {
        $user = User::create(['name' => 'Test User', 'email' => $email, 'password' => 'password-password', 'account_status' => 'active', 'user_type' => $type, 'email_verified_at' => now()]);
        $user->forceFill(['is_active' => true])->save();
        return $user;
    }

    private function makeAdmin(): User
    {
        $user = $this->makeUser('support-admin@example.com', 'staff');
        MfaMethod::create(['user_id' => $user->id, 'type' => 'totp', 'secret_encrypted' => 'test', 'is_primary' => true, 'verified_at' => now()]);
        $role = PlatformRole::where('name', 'platform_super_admin')->firstOrFail();
        PlatformAdminMembership::create(['user_id' => $user->id, 'platform_role_id' => $role->id, 'status' => 'active']);
        return $user;
    }
}
