<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

final class RedirectUrlValidator
{
    public function assertAllowed(?string $url, array $allowedHosts = []): void
    {
        if ($url === null || $url === '') return;
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (!in_array($scheme, ['https', 'http'], true) || $host === '' || isset($parts['user']) || isset($parts['pass'])) throw ValidationException::withMessages(['redirect_url' => 'Redirect URL must use a safe HTTP(S) origin.']);
        if ($scheme === 'http' && !in_array($host, ['localhost', '127.0.0.1', '::1'], true)) throw ValidationException::withMessages(['redirect_url' => 'Non-local redirect URLs must use HTTPS.']);
        if ($allowedHosts && !in_array($host, array_map('strtolower', $allowedHosts), true)) throw ValidationException::withMessages(['redirect_url' => 'Redirect host is not allowed for this merchant.']);
    }
}
