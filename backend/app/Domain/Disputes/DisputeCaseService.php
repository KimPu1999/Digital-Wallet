<?php
namespace App\Domain\Disputes;
use App\Models\DisputeCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
final class DisputeCaseService
{
    private array $transitions = ['open' => ['assigned','investigating','escalated'], 'assigned' => ['investigating','escalated'], 'investigating' => ['awaiting_information','escalated','resolved'], 'awaiting_information' => ['investigating','resolved'], 'escalated' => ['investigating','resolved'], 'resolved' => ['closed'], 'closed' => []];
    public function create(array $data, Request $request): DisputeCase { $case = DB::transaction(function () use ($data, $request): DisputeCase { $case = DisputeCase::create($data + ['opened_by' => $request->user()->id, 'dispute_reference' => 'DSP-'.strtoupper(Str::random(10)), 'status' => 'open']); $case->events()->create(['actor_id' => $request->user()->id, 'event_type' => 'case_created', 'to_status' => 'open', 'notes' => $case->reason, 'metadata' => ['sandbox_only' => true], 'created_at' => now()]); return $case; }); return $case->load('events'); }
    public function transition(DisputeCase $case, string $status, string $notes, Request $request): DisputeCase { if (!in_array($status, $this->transitions[$case->status] ?? [], true)) throw ValidationException::withMessages(['status' => 'Invalid dispute transition.']); $from = $case->status; $case->update(['status' => $status, 'resolution_summary' => in_array($status, ['resolved','closed'], true) ? $notes : $case->resolution_summary]); $case->events()->create(['actor_id' => $request->user()->id, 'event_type' => 'status_changed', 'from_status' => $from, 'to_status' => $status, 'notes' => $notes, 'metadata' => ['sandbox_only' => true], 'created_at' => now()]); return $case->refresh()->load('events'); }
}
