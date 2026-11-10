<?php
namespace App\Domain\QA;
use App\Models\QaFinding;
use App\Models\QaPerformanceRun;
use App\Models\QaRecoveryDrill;
use App\Models\QaReleaseGate;
use App\Models\QaTestRun;
final class QaAssessmentService {
    public function overview(): array {
        $latest = QaTestRun::query()->latest('finished_at')->get()->groupBy('category')->map(fn ($runs) => $runs->first())->values();
        return ['sandbox_only'=>true, 'test_runs'=>['total'=>QaTestRun::count(),'latest'=>$latest], 'findings'=>['open'=>QaFinding::whereIn('status',['open','in_progress'])->count(),'critical'=>QaFinding::where('severity','critical')->whereIn('status',['open','in_progress'])->count()], 'performance_runs'=>QaPerformanceRun::latest()->limit(10)->get(), 'recovery_drills'=>QaRecoveryDrill::latest()->limit(10)->get(), 'release_gates'=>$this->gates()];
    }
    public function gates(): array {
        $definitions = ['critical_functional'=>'functional','financial_integrity'=>'financial_integrity','authorization_regression'=>'security','performance_evaluated'=>'performance','backup_restore_verified'=>'recovery','monitoring_configured'=>'monitoring'];
        $result = [];
        foreach ($definitions as $key => $category) {
            $stored = QaReleaseGate::where('gate_key',$key)->first();
            if ($stored) { $result[] = $stored; continue; }
            $run = QaTestRun::where('category',$category)->latest('finished_at')->first();
            $status = $run?->status === 'passed' ? 'ready' : 'blocked';
            $result[] = ['gate_key'=>$key,'status'=>$status,'required'=>true,'evidence_reference'=>$run?->artifact_uri,'reason'=>$run ? 'Latest evidence did not pass.' : 'No evidence recorded.'];
        }
        return $result;
    }
    public function testRuns() { return QaTestRun::latest('created_at')->paginate(10); }
    public function findings() { return QaFinding::latest('created_at')->paginate(10); }
    public function performance() { return QaPerformanceRun::latest('created_at')->paginate(10); }
    public function recovery() { return QaRecoveryDrill::latest('created_at')->paginate(10); }
}
