<?php
namespace App\Http\Controllers\Api\V1;
use App\Domain\Disputes\DisputeCaseService;
use App\Http\Requests\DisputeRequest;
use App\Http\Requests\DisputeTransitionRequest;
use App\Models\DisputeCase;
use App\Models\PaymentIntent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
final class DisputeController
{
    public function index(Request $request): JsonResponse { return response()->json(['data' => DisputeCase::where('user_id', $request->user()->id)->with('events')->latest()->paginate(10), 'sandbox_only' => true]); }
    public function create(DisputeRequest $request, DisputeCaseService $service): JsonResponse { if ($request->filled('payment_intent_id')) abort_unless(PaymentIntent::whereKey($request->string('payment_intent_id')->toString())->where('user_id', $request->user()->id)->exists(), 404, 'Payment reference not found.'); return response()->json(['data' => $service->create(['user_id' => $request->user()->id, 'classification' => $request->string('classification')->toString(), 'reason' => $request->string('reason')->toString(), 'payment_intent_id' => $request->input('payment_intent_id'), 'priority' => $request->input('priority', 'normal'), 'support_ticket_id' => $request->input('support_ticket_id')], $request), 'sandbox_only' => true], 201); }
    public function show(Request $request, string $id): JsonResponse { return response()->json(['data' => DisputeCase::whereKey($id)->where('user_id', $request->user()->id)->with('events')->firstOrFail(), 'sandbox_only' => true]); }
    public function platformIndex(Request $request): JsonResponse { $page = DisputeCase::with('events')->when($request->filled('status'), fn ($q) => $q->where('status', $request->query('status')))->latest()->paginate(10); return response()->json(['data' => $page->items(), 'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()], 'sandbox_only' => true]); }
    public function platformTransition(DisputeTransitionRequest $request, string $id, DisputeCaseService $service): JsonResponse { return response()->json(['data' => $service->transition(DisputeCase::findOrFail($id), $request->string('status')->toString(), $request->string('notes')->toString(), $request), 'sandbox_only' => true]); }
}
