<?php

namespace App\Services;

use App\Domain\Ledger\IdempotencyResult;
use App\Models\IdempotencyKey;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class IdempotencyService
{
    public function execute(string $scope, string $key, array $payload, callable $callback): IdempotencyResult
    {
        if ($key === '' || strlen($key) > 255) throw ValidationException::withMessages(['idempotency_key' => 'A valid idempotency key is required.']);
        $fingerprint = hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return DB::transaction(function () use ($scope, $key, $fingerprint, $callback): IdempotencyResult {
            DB::table('idempotency_keys')->insertOrIgnore(['id' => (string) Str::uuid(), 'scope' => $scope, 'key' => $key, 'request_fingerprint' => $fingerprint, 'status' => 'reserved', 'expires_at' => now()->addHours(24), 'created_at' => now(), 'updated_at' => now()]);
            $record = IdempotencyKey::where('scope', $scope)->where('key', $key)->lockForUpdate()->firstOrFail();
            if (!hash_equals($record->request_fingerprint, $fingerprint)) throw ValidationException::withMessages(['idempotency_key' => 'The idempotency key was reused with a different request.']);
            if ($record->status === 'completed') return new IdempotencyResult($record->response_body, true);
            $result = $callback();
            $body = is_array($result) ? $result : ['result' => $result];
            $record->update(['status' => 'completed', 'response_code' => 200, 'response_body' => $body]);
            return new IdempotencyResult($body, false);
        }, 3);
    }
}
