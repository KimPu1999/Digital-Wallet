<?php
namespace App\Domain\Fx;
use App\Models\FxPair;
interface FxRateProviderInterface { public function code(): string; public function rate(FxPair $pair): FxProviderRate; }
