<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

final class WebhookUrlValidator
{
    public function assertPublicHttps(string $url): void
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($scheme !== 'https' || $host === '' || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) throw ValidationException::withMessages(['url' => 'Webhook URL must be HTTPS without credentials or an explicit port.']);
        if (in_array($host, ['localhost', 'localhost.localdomain'], true) || filter_var($host, FILTER_VALIDATE_IP) && !filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) throw ValidationException::withMessages(['url' => 'Private or local webhook destinations are not allowed.']);
        $resolved = gethostbyname($host);
        if (filter_var($resolved, FILTER_VALIDATE_IP) && !filter_var($resolved, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) throw ValidationException::withMessages(['url' => 'Webhook destination resolves to a private or reserved network.']);
    }
}
