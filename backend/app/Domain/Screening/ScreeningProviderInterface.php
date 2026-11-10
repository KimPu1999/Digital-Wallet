<?php
namespace App\Domain\Screening;

interface ScreeningProviderInterface
{
    public function name(): string;
    public function screen(array $subject): array;
}
