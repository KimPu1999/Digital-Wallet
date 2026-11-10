<?php
namespace App\Domain\Fx;
final readonly class FxProviderRate { public function __construct(public string $rate, public string $providerReference, public int $ttlSeconds = 60, public array $metadata = []) {} }
