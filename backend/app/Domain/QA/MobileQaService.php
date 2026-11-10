<?php

namespace App\Domain\QA;

use App\Models\MobileBuild;
use App\Models\MobileCrashReport;
use App\Models\MobileFeedback;
use App\Models\MobileReleaseCandidate;
use App\Models\QaTestRun;

final class MobileQaService
{
    public function overview(): array
    {
        return [
            'sandbox_only' => true,
            'builds' => ['total' => MobileBuild::count(), 'latest' => MobileBuild::latest()->limit(10)->get()],
            'crashes' => ['open' => MobileCrashReport::whereIn('status', ['open', 'investigating'])->count(), 'critical' => MobileCrashReport::where('severity', 'critical')->whereIn('status', ['open', 'investigating'])->count()],
            'feedback' => ['open' => MobileFeedback::whereIn('status', ['open', 'triaged', 'in_progress'])->count()],
            'release_candidates' => MobileReleaseCandidate::latest()->limit(10)->get(),
            'release_gates' => $this->gates(),
        ];
    }

    public function gates(): array
    {
        $definitions = [
            'mobile_critical_tests' => 'mobile_functional',
            'mobile_security' => 'mobile_security',
            'mobile_performance' => 'mobile_performance',
            'mobile_accessibility' => 'mobile_accessibility',
            'mobile_localization' => 'mobile_localization',
        ];
        $gates = [];
        foreach ($definitions as $key => $category) {
            $run = QaTestRun::where('category', $category)->latest('finished_at')->first();
            $gates[] = ['gate_key' => $key, 'status' => $run?->status === 'passed' ? 'ready' : 'blocked', 'evidence_reference' => $run?->artifact_uri, 'reason' => $run ? 'Latest evidence did not pass.' : 'No evidence recorded.'];
        }

        foreach (['android_build' => 'android', 'ios_build' => 'ios'] as $key => $platform) {
            $build = MobileBuild::where('platform', $platform)->where('status', 'passed')->latest('finished_at')->first();
            $gates[] = ['gate_key' => $key, 'status' => $build ? 'ready' : 'blocked', 'evidence_reference' => $build?->artifact_uri, 'reason' => $build ? null : 'No passing build evidence recorded.'];
        }

        $candidate = MobileReleaseCandidate::where('status', 'approved')->latest('approved_at')->first();
        $gates[] = ['gate_key' => 'beta_approval', 'status' => $candidate ? 'ready' : 'blocked', 'evidence_reference' => $candidate?->id, 'reason' => $candidate ? null : 'No approved beta candidate recorded.'];
        return $gates;
    }

    public function builds() { return MobileBuild::latest('created_at')->paginate(10); }
    public function crashes() { return MobileCrashReport::latest('created_at')->paginate(10); }
    public function feedback() { return MobileFeedback::latest('created_at')->paginate(10); }
    public function candidates() { return MobileReleaseCandidate::latest('created_at')->paginate(10); }
}
