<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityDocument;
use App\Models\ActivityLog;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ActivityDocumentService
{
    protected array $disallowedExtensions = ['php', 'phtml', 'phar', 'exe', 'sh', 'bat', 'cmd', 'js', 'html', 'htm', 'svg', 'cgi', 'pl'];

    public function uploadDocument(Activity $activity, UploadedFile $file, int $documentTypeId, ?int $realizationId, User $user): ActivityDocument
    {
        return DB::transaction(function () use ($activity, $file, $documentTypeId, $realizationId, $user) {
            if ($activity->isClosedOrLocked()) {
                throw ValidationException::withMessages([
                    'file' => 'Dokumen tidak dapat diunggah pada kegiatan yang dibatalkan atau tahun anggaran yang ditutup.',
                ]);
            }

            $documentType = DocumentType::find($documentTypeId);
            if (! $documentType || ! $documentType->is_active) {
                throw ValidationException::withMessages([
                    'document_type_id' => 'Jenis dokumen tidak aktif atau tidak ditemukan.',
                ]);
            }

            $ext = strtolower($file->getClientOriginalExtension());
            if (in_array($ext, $this->disallowedExtensions)) {
                throw ValidationException::withMessages([
                    'file' => "Format file .{$ext} dilarang demi keamanan sistem.",
                ]);
            }

            // Check allowed extensions from document_type
            $allowedList = array_map('trim', explode(',', strtolower($documentType->allowed_extensions)));
            if (! in_array($ext, $allowedList)) {
                throw ValidationException::withMessages([
                    'file' => "Format file .{$ext} tidak sesuai. Ekstensi yang diizinkan: {$documentType->allowed_extensions}",
                ]);
            }

            // Check size (in KB)
            $sizeKb = (int) round($file->getSize() / 1024);
            if ($sizeKb > $documentType->maximum_size) {
                throw ValidationException::withMessages([
                    'file' => "Ukuran file ({$sizeKb} KB) melebihi batas maksimal yang diizinkan ({$documentType->maximum_size} KB).",
                ]);
            }

            // Determine directory path
            $year = $activity->budgetYear->year;
            $unitCode = $activity->unit ? $activity->unit->code : 'SKR';
            $relativeDir = "private/documents/{$year}/{$unitCode}/{$activity->id}";
            $storedName = (string) Str::uuid().'.'.$ext;
            $filePath = $file->storeAs($relativeDir, $storedName);

            // Check if previous current version exists for this activity & document_type
            $previousDoc = ActivityDocument::where('activity_id', $activity->id)
                ->where('document_type_id', $documentType->id)
                ->where('is_current', true)
                ->first();

            $nextVersion = 1;
            if ($previousDoc) {
                $nextVersion = $previousDoc->version + 1;
                $previousDoc->update(['is_current' => false]);
            }

            $document = ActivityDocument::create([
                'activity_id' => $activity->id,
                'document_type_id' => $documentType->id,
                'realization_id' => $realizationId,
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $storedName,
                'file_path' => $filePath,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'status' => 'uploaded',
                'version' => $nextVersion,
                'is_current' => true,
                'uploaded_by' => $user->id,
                'uploaded_at' => now(),
            ]);

            ActivityLog::log('upload_document', 'Dokumen', "Mengunggah dokumen '{$documentType->name}' (Versi {$nextVersion}) untuk kegiatan {$activity->activity_code}", $activity);

            return $document;
        });
    }

    public function replaceDocument(ActivityDocument $document, UploadedFile $file, User $user): ActivityDocument
    {
        return $this->uploadDocument($document->activity, $file, $document->document_type_id, $document->realization_id, $user);
    }

    public function deleteDocument(ActivityDocument $document, User $user): void
    {
        DB::transaction(function () use ($document, $user) {
            $activity = $document->activity;

            if ($activity->isClosedOrLocked() || $document->status !== 'uploaded') {
                throw ValidationException::withMessages([
                    'status' => 'Dokumen yang telah diajukan atau dalam tahun ditutup tidak dapat dihapus.',
                ]);
            }

            $docName = $document->original_name;
            $path = $document->file_path;

            // Mark previous version as current if exists
            if ($document->is_current) {
                $prevVersion = ActivityDocument::where('activity_id', $activity->id)
                    ->where('document_type_id', $document->document_type_id)
                    ->where('id', '!=', $document->id)
                    ->orderBy('version', 'desc')
                    ->first();

                if ($prevVersion) {
                    $prevVersion->update(['is_current' => true]);
                }
            }

            $document->delete();

            if (Storage::exists($path)) {
                Storage::delete($path);
            }

            ActivityLog::log('delete_document', 'Dokumen', "Menghapus dokumen '{$docName}' dari kegiatan {$activity->activity_code}", $activity);
        });
    }

    public function downloadDocument(ActivityDocument $document, User $user, bool $inline = false)
    {
        if (! Storage::exists($document->file_path)) {
            abort(404, 'Dokumen fisik tidak ditemukan di penyimpanan server.');
        }

        $action = $inline ? 'preview_document' : 'download_document';
        ActivityLog::log($action, 'Dokumen', "Membuka/mengunduh dokumen '{$document->original_name}'", $document->activity);

        $headers = [
            'Content-Type' => $document->mime_type,
        ];

        if ($inline) {
            return response()->file(Storage::path($document->file_path), $headers);
        }

        return Storage::download($document->file_path, $document->original_name, $headers);
    }
}
