<?php

namespace App\Services;

final class ProviderErrorMapper
{
    public function safe(string $provider, ?string $message): string
    {
        return $message ? "{$provider}: ".substr(preg_replace('/[\r\n]+/', ' ', $message) ?? 'Provider error', 0, 220) : "{$provider}: Provider operation failed.";
    }
}
