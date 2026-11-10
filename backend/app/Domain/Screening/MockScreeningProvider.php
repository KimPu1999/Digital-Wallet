<?php
namespace App\Domain\Screening;

use RuntimeException;

final class MockScreeningProvider implements ScreeningProviderInterface
{
    public function name(): string { return 'mock_sandbox'; }

    public function screen(array $subject): array
    {
        $mode = $subject['mock_mode'] ?? 'no_match';
        if ($mode === 'timeout') throw new RuntimeException('Sandbox screening provider timed out.');
        if ($mode === 'error') throw new RuntimeException('Sandbox screening provider failed.');
        if ($mode === 'potential_match') return ['status' => 'potential_match', 'matches' => [['status' => 'potential_match', 'list_source' => 'sandbox_demo_list', 'match_reference' => 'sandbox-match-'.substr(sha1((string) ($subject['subject_id'] ?? 'unknown')), 0, 12), 'match_score' => 0.42]]];
        return ['status' => 'no_match', 'matches' => []];
    }
}
