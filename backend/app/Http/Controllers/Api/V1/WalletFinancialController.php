<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\LedgerEntryResource;
use App\Models\Wallet;
use App\Services\BalanceProjectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\WalletCurrencyAccount;

final class WalletFinancialController
{
    public function balance(Request $request, Wallet $wallet, BalanceProjectionService $projections): JsonResponse
    {
        $this->authorizeWallet($request, $wallet);
        $accounts = $wallet->currencyAccounts()->with(['currency', 'projection'])->get()->map(function (WalletCurrencyAccount $account) use ($projections): array {
            return ['currency_code' => $account->currency->code, 'currency_name' => $account->currency->name, 'account_status' => $account->status, 'balance' => $projections->get($account)];
        });
        return response()->json(['data' => ['wallet_id' => $wallet->id, 'accounts' => $accounts, 'ledger_source_of_truth' => true, 'sandbox_only' => true]]);
    }

    public function activity(Request $request, Wallet $wallet): JsonResponse
    {
        $this->authorizeWallet($request, $wallet);
        $entries = \App\Models\LedgerEntry::with(['transaction', 'account'])->whereHas('account', fn ($query) => $query->whereHas('walletCurrencyAccount', fn ($account) => $account->where('wallet_id', $wallet->id)))->latest('created_at')->paginate(min(max((int) $request->input('per_page', 10), 1), 10));
        return response()->json(['data' => LedgerEntryResource::collection($entries->items()), 'meta' => ['current_page' => $entries->currentPage(), 'last_page' => $entries->lastPage(), 'per_page' => $entries->perPage(), 'total' => $entries->total()], 'sandbox_only' => true]);
    }

    private function authorizeWallet(Request $request, Wallet $wallet): void
    {
        abort_unless(app(\App\Policies\WalletPolicy::class)->view($request->user(), $wallet->load('owner')), 404, 'Wallet not found.');
    }
}
