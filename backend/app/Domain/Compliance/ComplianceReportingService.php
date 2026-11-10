<?php
namespace App\Domain\Compliance;

use App\Models\RegulatoryReportDraft;
use App\Services\PlatformAuditLogger;
use Illuminate\Http\Request;

final class ComplianceReportingService
{
    public function createDraft(array $data, Request $request): RegulatoryReportDraft
    {
        $draft = RegulatoryReportDraft::create($data + ['created_by' => $request->user()->id, 'status' => 'draft']);
        app(PlatformAuditLogger::class)->record('compliance.report_draft.created', RegulatoryReportDraft::class, $draft->id, $request, [], ['status' => 'draft'], 'recorded', null, ['sandbox_only' => true]);
        return $draft;
    }
}
