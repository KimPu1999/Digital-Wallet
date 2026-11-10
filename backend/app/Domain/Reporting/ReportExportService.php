<?php
namespace App\Domain\Reporting;
use App\Models\ReportFile;
use App\Models\ReportRun;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
final class ReportExportService {
    public function csv(ReportRun $run, array $rows, string $actorId): ReportFile {
        $lines = [];
        if ($rows) { $headers = array_keys((array) $rows[0]); $lines[] = $this->line($headers); foreach ($rows as $row) $lines[] = $this->line(array_map(fn ($value) => is_array($value) ? json_encode($value) : $value, (array) $row)); }
        else $lines[] = $this->line(['message', 'No report rows available']);
        $contents = implode("\n", $lines)."\n";
        $key = 'reports/'.$run->id.'/'.Str::uuid().'.csv';
        Storage::disk('local')->put($key, $contents);
        $file = ReportFile::create(['report_run_id' => $run->id, 'format' => 'csv', 'storage_key' => $key, 'checksum' => hash('sha256', $contents), 'row_count' => max(0, count($lines) - 1), 'status' => 'ready', 'created_by' => $actorId, 'expires_at' => now()->addDays(7)]);
        return $file;
    }
    private function line(array $values): string { return implode(',', array_map(function ($value): string { $value = (string) ($value ?? ''); if (preg_match('/^[=+\-@]/', $value)) $value = "'".$value; return '"'.str_replace('"', '""', $value).'"'; }, $values)); }
}
