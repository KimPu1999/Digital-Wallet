<?php
namespace App\Http\Controllers\Api\V1;
use App\Domain\Notifications\NotificationPreferenceService;
use App\Http\Requests\NotificationPreferenceRequest;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
final class NotificationController
{
    public function index(Request $request): JsonResponse { $page = Notification::where('notifiable_type', \App\Models\User::class)->where('notifiable_id', $request->user()->id)->when($request->filled('category'), fn ($q) => $q->where('data->category', $request->query('category')))->latest()->paginate(10); return response()->json(['data' => $page->items(), 'meta' => $this->meta($page), 'sandbox_only' => true]); }
    public function unreadCount(Request $request): JsonResponse { return response()->json(['data' => ['unread' => Notification::where('notifiable_type', \App\Models\User::class)->where('notifiable_id', $request->user()->id)->whereNull('read_at')->count()], 'sandbox_only' => true]); }
    public function read(Request $request, string $id): JsonResponse { $notification = $this->owned($request, $id); $notification->update(['read_at' => now()]); return response()->json(['data' => $notification->refresh(), 'sandbox_only' => true]); }
    public function markAllRead(Request $request): JsonResponse { Notification::where('notifiable_type', \App\Models\User::class)->where('notifiable_id', $request->user()->id)->whereNull('read_at')->update(['read_at' => now()]); return response()->json(['message' => 'Notifications marked as read.', 'sandbox_only' => true]); }
    public function preferences(Request $request, NotificationPreferenceService $service): JsonResponse { return response()->json(['data' => $service->get($request->user()), 'sandbox_only' => true]); }
    public function updatePreferences(NotificationPreferenceRequest $request, NotificationPreferenceService $service): JsonResponse { return response()->json(['data' => $service->update($request->user(), $request->validated()), 'sandbox_only' => true]); }
    private function owned(Request $request, string $id): Notification { return Notification::whereKey($id)->where('notifiable_type', \App\Models\User::class)->where('notifiable_id', $request->user()->id)->firstOrFail(); }
    private function meta($page): array { return ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()]; }
}
