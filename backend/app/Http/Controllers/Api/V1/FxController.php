<?php
namespace App\Http\Controllers\Api\V1;
use App\Http\Requests\CreateFxQuoteRequest;
use App\Http\Resources\FxConversionResource; use App\Http\Resources\FxQuoteResource;
use App\Models\FxConversion; use App\Models\FxPair; use App\Models\FxQuote; use App\Models\WalletCurrencyAccount;
use App\Services\FxConversionService; use App\Services\FxQuoteService; use Illuminate\Http\JsonResponse; use Illuminate\Http\Request;
final class FxController
{
    public function pairs(): JsonResponse { return response()->json(['data'=>FxPair::where('is_enabled',true)->orderBy('base_currency_code')->orderBy('quote_currency_code')->get(['id','base_currency_code','quote_currency_code','min_amount_minor','max_amount_minor','quote_expiry_seconds']),'sandbox_only'=>true]); }
    public function quote(CreateFxQuoteRequest $request, FxQuoteService $service): JsonResponse { $source=WalletCurrencyAccount::findOrFail($request->string('source_wallet_currency_account_id')->toString()); $target=WalletCurrencyAccount::findOrFail($request->string('target_wallet_currency_account_id')->toString()); $quote=$service->create($request->user(),$source,$target,(int)$request->integer('source_amount_minor')); return response()->json(['data'=>new FxQuoteResource($quote),'sandbox_only'=>true],201); }
    public function showQuote(Request $request, FxQuote $quote): JsonResponse { abort_unless((string)$quote->user_id === (string)$request->user()->id,404); return response()->json(['data'=>new FxQuoteResource($quote),'sandbox_only'=>true]); }
    public function convert(Request $request, FxQuote $quote, FxConversionService $service): JsonResponse { $key=(string)$request->header('Idempotency-Key'); $conversion=$service->execute($request->user(),$quote,$key); return response()->json(['data'=>new FxConversionResource($conversion),'sandbox_only'=>true]); }
    public function conversions(Request $request): JsonResponse { $rows=FxConversion::where('user_id',$request->user()->id)->latest()->paginate(10); return response()->json(['data'=>FxConversionResource::collection($rows->items()),'meta'=>['current_page'=>$rows->currentPage(),'last_page'=>$rows->lastPage(),'per_page'=>10,'total'=>$rows->total()],'sandbox_only'=>true]); }
    public function conversion(Request $request, FxConversion $conversion): JsonResponse { abort_unless((string)$conversion->user_id === (string)$request->user()->id,404); return response()->json(['data'=>new FxConversionResource($conversion->load('legs')),'sandbox_only'=>true]); }
}
