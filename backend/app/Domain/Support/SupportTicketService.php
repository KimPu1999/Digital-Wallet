<?php
namespace App\Domain\Support;
use App\Domain\Notifications\NotificationEventService;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
final class SupportTicketService
{
    private array $transitions = ['open' => ['assigned','in_progress','closed'], 'assigned' => ['in_progress','escalated'], 'in_progress' => ['awaiting_customer','escalated','resolved'], 'awaiting_customer' => ['in_progress','closed'], 'escalated' => ['in_progress','resolved'], 'resolved' => ['closed','reopened'], 'closed' => ['reopened'], 'reopened' => ['assigned','in_progress']];
    public function __construct(private SupportSlaService $sla, private NotificationEventService $notifications) {}
    public function create(User $actor, array $data, ?string $merchantId = null): SupportTicket
    {
        return DB::transaction(function () use ($actor, $data, $merchantId): SupportTicket {
            $ticket = SupportTicket::create(['user_id' => $merchantId ? null : $actor->id, 'merchant_id' => $merchantId, 'created_by' => $actor->id, 'ticket_reference' => 'SUP-'.strtoupper(Str::random(10)), 'category' => $data['category'], 'subject' => $data['subject'], 'priority' => $data['priority'] ?? 'normal', 'status' => 'open', 'sla_due_at' => $this->sla->dueAt($data['priority'] ?? 'normal'), 'metadata' => ['sandbox_only' => true]]);
            $this->event($ticket, $actor, 'ticket_created', null, 'open', $data['message'] ?? null);
            if (!empty($data['message'])) $this->message($ticket, $actor, $data['message'], 'customer', $data['client_message_key'] ?? null);
            return $ticket->load(['messages','events']);
        });
    }
    public function message(SupportTicket $ticket, User $actor, string $body, string $senderType = 'customer', ?string $clientKey = null): SupportMessage
    {
        if ($clientKey && ($existing = SupportMessage::where('support_ticket_id', $ticket->id)->where('client_message_key', $clientKey)->first())) return $existing;
        $message = SupportMessage::create(['support_ticket_id' => $ticket->id, 'sender_id' => $actor->id, 'sender_type' => $senderType, 'visibility' => $senderType === 'internal' ? 'internal' : 'customer', 'body' => trim($body), 'client_message_key' => $clientKey]);
        if ($senderType === 'agent' && !$ticket->first_response_at) $ticket->update(['first_response_at' => now()]);
        if ($ticket->user_id && $senderType !== 'customer') $this->notifications->emit($ticket->user, 'support-message|'.$message->id, 'support.ticket.replied', 'support_ticket_reply', ['reference' => $ticket->ticket_reference, 'locale' => 'en'], 'support');
        return $message;
    }
    public function transition(SupportTicket $ticket, User $actor, string $status, ?string $notes = null): SupportTicket
    {
        if (!in_array($status, $this->transitions[$ticket->status] ?? [], true)) throw ValidationException::withMessages(['status' => 'Invalid support ticket transition.']);
        $from = $ticket->status; $ticket->update(['status' => $status, 'resolved_at' => in_array($status, ['resolved','closed'], true) ? now() : null, 'closed_at' => $status === 'closed' ? now() : null, 'resolution_summary' => in_array($status, ['resolved','closed'], true) ? $notes : $ticket->resolution_summary]); $this->event($ticket, $actor, 'status_changed', $from, $status, $notes); if ($ticket->user_id) $this->notifications->emit($ticket->user, 'support-status|'.$ticket->id.'|'.$status, 'support.ticket.updated', 'support_ticket_update', ['reference' => $ticket->ticket_reference, 'status' => $status, 'locale' => 'en'], 'support'); return $ticket->refresh();
    }
    private function event(SupportTicket $ticket, User $actor, string $type, ?string $from, ?string $to, ?string $notes): void { $ticket->events()->create(['actor_id' => $actor->id, 'event_type' => $type, 'from_status' => $from, 'to_status' => $to, 'notes' => $notes, 'metadata' => ['sandbox_only' => true], 'created_at' => now()]); }
}
