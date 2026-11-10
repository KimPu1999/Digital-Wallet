<?php
namespace App\Domain\Support;
use App\Models\SupportAssignment;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Http\Request;
final class SupportAssignmentService
{
    public function assign(SupportTicket $ticket, User $agent, Request $request): SupportTicket { SupportAssignment::where('support_ticket_id', $ticket->id)->whereNull('unassigned_at')->update(['unassigned_at' => now()]); SupportAssignment::create(['support_ticket_id' => $ticket->id, 'agent_id' => $agent->id, 'assigned_by' => $request->user()->id, 'assigned_at' => now()]); $ticket->update(['assigned_to' => $agent->id, 'status' => $ticket->status === 'open' ? 'assigned' : $ticket->status]); return $ticket->refresh(); }
}
