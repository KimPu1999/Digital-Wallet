<?php
namespace App\Domain\Transfers;
use App\Models\Transfer;
interface TransferProviderInterface { public function name(): string; public function submit(Transfer $transfer): TransferProviderResult; }
