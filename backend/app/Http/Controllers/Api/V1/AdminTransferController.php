<?php
namespace App\Http\Controllers\Api\V1;
use App\Models\Transfer; use Illuminate\Http\JsonResponse; use Illuminate\Http\Request;
final class AdminTransferController
{
    public function index(Request $request): JsonResponse { $query=Transfer::with('beneficiary')->latest(); if($status=$request->string('status')->toString()) $query->where('status',$status); $rows=$query->paginate(10); return response()->json(['data'=>$rows->items(),'meta'=>['current_page'=>$rows->currentPage(),'last_page'=>$rows->lastPage(),'total'=>$rows->total()],'sandbox_only'=>true]); }
    public function show(Transfer $transfer): JsonResponse { return response()->json(['data'=>$transfer->load(['beneficiary','events','screenings','attempts','receipt']),'sandbox_only'=>true]); }
    public function reviewQueue(): JsonResponse { return response()->json(['data'=>Transfer::with('beneficiary')->whereIn('status',['pending_compliance','processing'])->latest()->paginate(10),'sandbox_only'=>true]); }
    public function riskAlerts(): JsonResponse { return response()->json(['data'=>\App\Models\TransferScreening::with('transfer')->whereIn('status',['review','failed','rejected'])->latest()->paginate(10),'sandbox_only'=>true]); }
}
