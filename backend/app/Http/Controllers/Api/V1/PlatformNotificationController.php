<?php
namespace App\Http\Controllers\Api\V1;
use App\Models\NotificationDelivery;
use App\Models\NotificationTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
final class PlatformNotificationController
{
    public function overview(): JsonResponse { return response()->json(['data' => ['notifications' => DB::table('notifications')->count(), 'unread' => DB::table('notifications')->whereNull('read_at')->count(), 'deliveries' => NotificationDelivery::count(), 'failed_deliveries' => NotificationDelivery::where('status', 'failed')->count(), 'sandbox_only' => true], 'sandbox_only' => true]); }
    public function templates(): JsonResponse { return response()->json(['data' => NotificationTemplate::orderBy('template_key')->orderBy('locale')->paginate(10), 'sandbox_only' => true]); }
    public function deliveries(Request $request): JsonResponse { return response()->json(['data' => NotificationDelivery::latest()->paginate(10), 'sandbox_only' => true]); }
    public function failures(Request $request): JsonResponse { return response()->json(['data' => NotificationDelivery::whereIn('status', ['failed','mock_queued'])->latest()->paginate(10), 'message' => 'Mock-queued channels are not evidence of delivery.', 'sandbox_only' => true]); }
}
