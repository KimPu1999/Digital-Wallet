<?php
namespace App\Services;
use App\Domain\Transfers\TransferProviderInterface; use App\Domain\Transfers\TransferProviderResult; use App\Models\Transfer;
final class SandboxTransferProvider implements TransferProviderInterface
{
    public function name(): string { return 'sandbox-mock'; }
    public function submit(Transfer $transfer): TransferProviderResult { $scenario=(string)($transfer->metadata['provider_scenario'] ?? 'accepted'); $reference='sandbox-transfer-'.$transfer->id; return match($scenario) { 'processing' => new TransferProviderResult('processing',$reference), 'failed' => new TransferProviderResult('failed',$reference,'Sandbox provider failure'), 'timeout' => new TransferProviderResult('timed_out',$reference,'Sandbox provider timeout'), 'returned' => new TransferProviderResult('returned',$reference,'Sandbox provider returned funds'), default => new TransferProviderResult('completed',$reference) }; }
}
