<?php

namespace App\Services;

final class ProviderStatusMapper
{
    public function intentStatus(string $providerStatus): string
    {
        return match ($providerStatus) {
            'succeeded', 'completed', 'settled' => 'succeeded',
            'failed', 'declined' => 'failed',
            'returned' => 'returned',
            'unknown', 'timeout' => 'unknown',
            default => 'processing',
        };
    }
}
