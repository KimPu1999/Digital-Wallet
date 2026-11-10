<?php
namespace App\Services;
use App\Models\AuditLog; use Illuminate\Http\Request;
final class AuditLogger { public function record(string $action, ?Request $request = null, array $metadata = []): void { AuditLog::create(['user_id'=>$request?->user()?->id,'action'=>$action,'metadata'=>$metadata,'ip_address'=>$request?->ip()]); } }
