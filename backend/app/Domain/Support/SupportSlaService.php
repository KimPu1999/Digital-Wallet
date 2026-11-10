<?php
namespace App\Domain\Support;
use App\Models\SupportSlaPolicy;
use Carbon\Carbon;
final class SupportSlaService
{
    public function dueAt(string $priority): ?Carbon { $policy = SupportSlaPolicy::where('priority', $priority)->where('status', 'active')->latest('version')->first(); return $policy ? now()->addMinutes((int) $policy->resolution_minutes) : null; }
    public function breached($ticket): bool { return $ticket->sla_due_at !== null && $ticket->sla_due_at->isPast() && !in_array($ticket->status, ['resolved','closed'], true); }
}
