<?php

namespace App\Services\Employee;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeeDocumentService
{
    private string $disk = 'local';

    public function __construct(
        private AuditService $audit
    ) {}

    public function upload(
        Employee $employee,
        UploadedFile $file,
        array $data,
        ?User $uploadedBy = null
    ): EmployeeDocument {
        $this->validateFile($file);

        /*
         * Do not use the employee's name or the original
         * filename as the physical storage filename.
         */
        $extension = strtolower(
            $file->getClientOriginalExtension()
        );

        $filename = (string) Str::uuid()
            . ($extension !== '' ? ".{$extension}" : '');

        $directory =
            "employees/{$employee->id}/documents";

        $path = $file->storeAs(
            $directory,
            $filename,
            $this->disk
        );

        if ($path === false) {
            throw ValidationException::withMessages([
                'file' =>
                    'The employee document could not be stored.',
            ]);
        }

        try {
            return DB::transaction(function () use (
                $employee,
                $file,
                $data,
                $uploadedBy,
                $path
            ) {
                $employee = Employee::query()
                    ->lockForUpdate()
                    ->findOrFail($employee->id);

                $document = EmployeeDocument::create([
                    'employee_id' =>
                        $employee->id,

                    'document_type' =>
                        $data['document_type'],

                    'document_name' =>
                        $data['document_name']
                        ?? $file->getClientOriginalName(),

                    'file_path' =>
                        $path,

                    'mime_type' =>
                        $file->getMimeType(),

                    'file_size' =>
                        $file->getSize(),

                    'issued_at' =>
                        $data['issued_at'] ?? null,

                    'expires_at' =>
                        $data['expires_at'] ?? null,

                    'remarks' =>
                        $data['remarks'] ?? null,

                    'uploaded_by_user_id' =>
                        $uploadedBy?->id,
                ]);

                $this->audit->logModel(
                    module: 'employee',
                    action: 'document_uploaded',
                    auditableType: 'employee_document',
                    model: $document,
                    actor: $uploadedBy,
                    description:
                        "Uploaded a document for employee [{$employee->employee_number}].",
                    newValues:
                        $this->auditValues($document),
                );

                return $document;
            });
        } catch (\Throwable $e) {
            /*
             * Physical file was stored before the DB
             * transaction. Remove it if DB/audit fails.
             */
            Storage::disk($this->disk)
                ->delete($path);

            throw $e;
        }
    }

    public function download(
        EmployeeDocument $document
    ): StreamedResponse {
        $disk = Storage::disk($this->disk);

        if (! $disk->exists($document->file_path)) {
            abort(
                404,
                'Document file not found.'
            );
        }

        $downloadName =
            $this->safeDownloadName($document);

        $stream = $disk->readStream(
            $document->file_path
        );

        if ($stream === false) {
            abort(
                404,
                'Document file not found.'
            );
        }

        return response()->streamDownload(
            static function () use ($stream): void {
                try {
                    fpassthru($stream);
                } finally {
                    if (is_resource($stream)) {
                        fclose($stream);
                    }
                }
            },
            $downloadName
        );
    }

    /**
     * Replace the physical document while preserving
     * the database row.
     */
    public function replace(
        EmployeeDocument $document,
        UploadedFile $file,
        ?User $uploadedBy = null
    ): EmployeeDocument {
        $this->validateFile($file);

        $extension = strtolower(
            $file->getClientOriginalExtension()
        );

        $filename = (string) Str::uuid()
            . ($extension !== '' ? ".{$extension}" : '');

        $directory =
            "employees/{$document->employee_id}/documents";

        $newPath = $file->storeAs(
            $directory,
            $filename,
            $this->disk
        );

        if ($newPath === false) {
            throw ValidationException::withMessages([
                'file' =>
                    'The replacement document could not be stored.',
            ]);
        }

        /*
         * Do not trust stale model state.
         * The authoritative old path is captured after
         * locking the row inside the transaction.
         */
        $oldPath = null;

        try {
            $document = DB::transaction(function () use (
                $document,
                $file,
                $uploadedBy,
                $newPath,
                &$oldPath
            ) {
                $document = EmployeeDocument::query()
                    ->lockForUpdate()
                    ->findOrFail($document->id);

                $oldPath = $document->file_path;

                $oldValues =
                    $this->auditValues($document);

                $document->update([
                    'file_path' =>
                        $newPath,

                    'mime_type' =>
                        $file->getMimeType(),

                    'file_size' =>
                        $file->getSize(),

                    'uploaded_by_user_id' =>
                        $uploadedBy?->id,
                ]);

                $document->refresh();

                $this->audit->logModel(
                    module: 'employee',
                    action: 'document_replaced',
                    auditableType: 'employee_document',
                    model: $document,
                    actor: $uploadedBy,
                    description:
                        "Replaced employee document [{$document->id}].",
                    oldValues: $oldValues,
                    newValues:
                        $this->auditValues($document),
                );

                return $document;
            });
        } catch (\Throwable $e) {
            /*
             * DB/audit failed. The newly stored file
             * is not referenced and must be removed.
             */
            Storage::disk($this->disk)
                ->delete($newPath);

            throw $e;
        }

        /*
         * DB transaction has committed successfully.
         *
         * Only now can the old physical file be removed.
         */
        if (
            $oldPath !== null
            && $oldPath !== $newPath
            && Storage::disk($this->disk)
                ->exists($oldPath)
        ) {
            Storage::disk($this->disk)
                ->delete($oldPath);
        }

        return $document;
    }

    /**
     * Remove a document.
     *
     * Use this only for records that company retention
     * policy permits to be physically deleted.
     */
    public function delete(
        EmployeeDocument $document,
        ?User $actor = null
    ): void {
        /*
         * Capture the committed DB deletion first.
         * Physical file deletion happens afterward.
         */
        $path = null;

        DB::transaction(function () use (
            $document,
            $actor,
            &$path
        ) {
            $document = EmployeeDocument::query()
                ->lockForUpdate()
                ->findOrFail($document->id);

            $path = $document->file_path;

            $oldValues =
                $this->auditValues($document);

            /*
             * Audit BEFORE delete so the audit row and
             * deletion belong to the same transaction.
             */
            $this->audit->logModel(
                module: 'employee',
                action: 'document_deleted',
                auditableType: 'employee_document',
                model: $document,
                actor: $actor,
                description:
                    "Deleted employee document [{$document->id}].",
                oldValues: $oldValues,
            );

            $document->delete();
        });

        /*
         * DB deletion has committed.
         *
         * Storage deletion cannot participate in the DB
         * transaction, so perform it afterward.
         */
        if ($path !== null) {
            Storage::disk($this->disk)
                ->delete($path);
        }
    }

    private function validateFile(
        UploadedFile $file
    ): void {
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'file' =>
                    'The uploaded file is invalid.',
            ]);
        }

        /*
         * Defense-in-depth only.
         *
         * Exact mimes/extensions/max size should also
         * be enforced by the FormRequest.
         */
        $allowedMimeTypes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
        ];

        if (
            ! in_array(
                $file->getMimeType(),
                $allowedMimeTypes,
                true
            )
        ) {
            throw ValidationException::withMessages([
                'file' =>
                    'The file type is not allowed.',
            ]);
        }

        // 10 MB.
        if (
            ($file->getSize() ?? 0)
            > 10 * 1024 * 1024
        ) {
            throw ValidationException::withMessages([
                'file' =>
                    'The file must not exceed 10 MB.',
            ]);
        }
    }

    private function safeDownloadName(
        EmployeeDocument $document
    ): string {
        $name = trim(
            $document->document_name ?: 'document'
        );

        /*
         * Remove characters unsafe for download filenames.
         */
        $name = preg_replace(
            '/[^\pL\pN\-\_ .]/u',
            '',
            $name
        ) ?: 'document';

        $extension = pathinfo(
            $document->file_path,
            PATHINFO_EXTENSION
        );

        if (
            $extension !== ''
            && strtolower(
                pathinfo(
                    $name,
                    PATHINFO_EXTENSION
                )
            ) !== strtolower($extension)
        ) {
            $name .= ".{$extension}";
        }

        return $name;
    }

    /**
     * Controlled audit metadata only.
     *
     * Do not include file_path or file contents.
     */
    private function auditValues(
        EmployeeDocument $document
    ): array {
        return [
            'employee_id' =>
                $document->employee_id,

            'document_type' =>
                $document->document_type,

            'document_name' =>
                $document->document_name,

            'mime_type' =>
                $document->mime_type,

            'file_size' =>
                $document->file_size,

            'issued_at' =>
                $document->issued_at,

            'expires_at' =>
                $document->expires_at,

            'uploaded_by_user_id' =>
                $document->uploaded_by_user_id,
        ];
    }
}