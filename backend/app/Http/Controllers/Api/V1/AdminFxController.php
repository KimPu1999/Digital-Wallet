<?php
namespace App\Http\Controllers\Api\V1;
use App\Models\FxAuditEvent; use App\Models\FxConversion; use App\Models\FxPair; use App\Models\FxQuote; use App\Models\FxRateProvider; use App\Models\FxReconciliationItem; use Illuminate\Http\JsonResponse; use Illuminate\Http\Request;
final class AdminFxController
{
    public function overview(): JsonResponse { return response()->json(['data'=>['quotes'=>FxQuote::count(),'conversions'=>FxConversion::count(),'completed_conversions'=>FxConversion::where('status','completed')->count(),'failed_conversions'=>FxConversion::whereIn('status',['failed','rejected'])->count(),'enabled_pairs'=>FxPair::where('is_enabled',true)->count(),'providers'=>FxRateProvider::where('status','active')->count()],'sandbox_only'=>true]); }
    public function quotes(Request $request): JsonResponse { $rows=FxQuote::latest()->paginate(10); return response()->json(['data'=>$rows->items(),'meta'=>['current_page'=>$rows->currentPage(),'last_page'=>$rows->lastPage(),'total'=>$rows->total()],'sandbox_only'=>true]); }
    public function conversions(Request $request): JsonResponse { $rows=FxConversion::latest()->paginate(10); return response()->json(['data'=>$rows->items(),'meta'=>['current_page'=>$rows->currentPage(),'last_page'=>$rows->lastPage(),'total'=>$rows->total()],'sandbox_only'=>true]); }
    public function providers(): JsonResponse { return response()->json(['data'=>FxRateProvider::orderBy('code')->get(),'sandbox_only'=>true]); }
    public function reconciliation(): JsonResponse { return response()->json(['data'=>FxReconciliationItem::latest()->paginate(10),'sandbox_only'=>true]); }
    public function auditEvents(): JsonResponse { return response()->json(['data'=>FxAuditEvent::latest()->paginate(10),'sandbox_only'=>true]); }
}
