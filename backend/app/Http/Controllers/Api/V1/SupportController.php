<?php
namespace App\Http\Controllers\Api\V1;
use App\Domain\Support\SupportAssignmentService;
use App\Domain\Support\SupportTicketService;
use App\Http\Requests\SupportAssignmentRequest;
use App\Http\Requests\SupportAttachmentRequest;
use App\Http\Requests\SupportMessageRequest;
use App\Http\Requests\SupportTicketRequest;
use App\Http\Requests\SupportTransitionRequest;
use App\Models\Merchant;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\MerchantAccessService;
use App\Services\PrivateDocumentStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
final class SupportController
{
    public function customerIndex(Request $request): JsonResponse { return $this->page(SupportTicket::where('user_id', $request->user()->id)->with(['messages','events'])->latest()->paginate(10)); }
    public function customerCreate(SupportTicketRequest $request, SupportTicketService $service): JsonResponse { return response()->json(['data' => $service->create($request->user(), $request->validated()), 'sandbox_only' => true], 201); }
    public function customerShow(Request $request, string $id): JsonResponse { return response()->json(['data' => $this->customerTicket($request, $id)->load(['messages' => fn ($q) => $q->where('visibility', 'customer'), 'events','attachments']), 'sandbox_only' => true]); }
    public function customerMessage(SupportMessageRequest $request, string $id, SupportTicketService $service): JsonResponse { $ticket = $this->customerTicket($request, $id); return response()->json(['data' => $service->message($ticket, $request->user(), $request->string('body')->toString(), 'customer', $request->input('client_message_key')), 'sandbox_only' => true], 201); }
    public function attachment(SupportAttachmentRequest $request, string $id, PrivateDocumentStorage $storage): JsonResponse { $ticket = $this->customerTicket($request, $id); $file = $request->file('attachment'); $key = $storage->put($file, 'support/'.$ticket->id); $attachment = $ticket->attachments()->create(['uploaded_by' => $request->user()->id, 'storage_key' => $key, 'original_name' => $file->getClientOriginalName(), 'mime_type' => $file->getMimeType(), 'size_bytes' => $file->getSize(), 'scan_status' => 'pending', 'checksum' => hash_file('sha256', $file->getRealPath())]); return response()->json(['data' => $attachment, 'message' => 'Attachment received for malware scanning; it is not executable.', 'sandbox_only' => true], 201); }
    public function merchantIndex(Request $request, MerchantAccessService $access): JsonResponse { $merchant = $this->merchant($request, $access); return $this->page(SupportTicket::where('merchant_id', $merchant->id)->with(['messages','events'])->latest()->paginate(10)); }
    public function merchantCreate(SupportTicketRequest $request, SupportTicketService $service, MerchantAccessService $access): JsonResponse { $merchant = $this->merchant($request, $access); return response()->json(['data' => $service->create($request->user(), $request->validated(), $merchant->id), 'sandbox_only' => true], 201); }
    public function adminIndex(Request $request): JsonResponse { return $this->page(SupportTicket::with(['user:id,name,email','merchant:id,display_name'])->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->query('priority')))->when($request->boolean('unassigned'), fn ($q) => $q->whereNull('assigned_to'))->latest()->paginate(10)); }
    public function adminShow(string $id): JsonResponse { return response()->json(['data' => SupportTicket::with(['messages','events','attachments','user:id,name,email','merchant:id,display_name'])->findOrFail($id), 'sandbox_only' => true]); }
    public function assign(SupportAssignmentRequest $request, string $id, SupportAssignmentService $service): JsonResponse { $ticket = SupportTicket::findOrFail($id); $agent = User::findOrFail($request->string('agent_id')->toString()); abort_unless($agent->is_active && $agent->user_type === 'staff', 422, 'An active staff agent is required.'); return response()->json(['data' => $service->assign($ticket, $agent, $request), 'sandbox_only' => true]); }
    public function adminReply(SupportMessageRequest $request, string $id, SupportTicketService $service): JsonResponse { $ticket = SupportTicket::findOrFail($id); $visibility = $request->string('visibility')->toString() ?: 'customer'; return response()->json(['data' => $service->message($ticket, $request->user(), $request->string('body')->toString(), $visibility === 'internal' ? 'internal' : 'agent', $request->input('client_message_key')), 'sandbox_only' => true], 201); }
    public function transition(SupportTransitionRequest $request, string $id, SupportTicketService $service): JsonResponse { return response()->json(['data' => $service->transition(SupportTicket::findOrFail($id), $request->user(), $request->string('status')->toString(), $request->input('notes')), 'sandbox_only' => true]); }
    private function customerTicket(Request $request, string $id): SupportTicket { return SupportTicket::whereKey($id)->where('user_id', $request->user()->id)->firstOrFail(); }
    private function merchant(Request $request, MerchantAccessService $access): Merchant { $id = $request->input('merchant_id'); abort_unless($id, 422, 'merchant_id is required.'); $merchant = Merchant::findOrFail($id); $access->assert($request->user(), $merchant); return $merchant; }
    private function page($page): JsonResponse { return response()->json(['data' => $page->items(), 'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()], 'sandbox_only' => true]); }
}
