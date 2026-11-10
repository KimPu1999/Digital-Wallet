<?php
namespace App\Services;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
final class PrivateDocumentStorage { public function put(UploadedFile $file,string $directory): string { return $file->store($directory,'local'); } public function temporaryUrl(string $key): string { return Storage::disk('local')->temporaryUrl($key,now()->addMinutes(5)); } }
