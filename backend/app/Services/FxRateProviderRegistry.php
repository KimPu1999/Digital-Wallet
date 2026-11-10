<?php
namespace App\Services;
use App\Domain\Fx\FxRateProviderInterface;
use Illuminate\Contracts\Container\Container;
final class FxRateProviderRegistry { public function __construct(private Container $container) {} public function get(string $code): FxRateProviderInterface { $providers=config('fx.providers', []); $class=$providers[$code] ?? null; if(!$class) throw new \RuntimeException('FX provider is not configured.'); return $this->container->make($class); } }
