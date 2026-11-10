<?php
namespace App\Domain\Transfers;
final readonly class TransferProviderResult { public function __construct(public string $status, public string $reference, public ?string $failureReason = null) {} }
