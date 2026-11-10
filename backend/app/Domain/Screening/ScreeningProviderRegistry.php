<?php
namespace App\Domain\Screening;

use InvalidArgumentException;

final class ScreeningProviderRegistry
{
    public function __construct(private MockScreeningProvider $mock) {}
    public function get(string $provider): ScreeningProviderInterface
    {
        return match ($provider) { 'mock_sandbox' => $this->mock, default => throw new InvalidArgumentException('Screening provider is not configured.') };
    }
}
