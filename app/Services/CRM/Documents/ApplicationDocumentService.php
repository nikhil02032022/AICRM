<?php

declare(strict_types=1);

namespace App\Services\CRM\Documents;

use App\Enums\CRM\Documents\DocumentStatus;
use App\Enums\CRM\Documents\DocumentUploadChannel;
use App\Events\CRM\Documents\DocumentRejected;
use App\Events\CRM\Documents\DocumentUploaded;
use App\Events\CRM\Documents\DocumentVerified;
use App\Models\CRM\Application;
use App\Models\CRM\Documents\ApplicationDocument;
use App\Models\CRM\Documents\ApplicationDocumentComment;
use App\Models\CRM\Documents\DocumentChecklistItem;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

// BRD: CRM-DM-002, DM-003, DM-004 — Document submission, review, status lifecycle.
final class ApplicationDocumentService
{
    public function __construct(private DocumentEncryptionManager $encryption)
    {
    }

    public function upload(
        Application $application,
        DocumentChecklistItem $item,
        UploadedFile $file,
        DocumentUploadChannel $channel = DocumentUploadChannel::PORTAL,
    ): ApplicationDocument {
        $this->assertAllowed($item, $file);

        return DB::transaction(function () use ($application, $item, $file, $channel): ApplicationDocument {
            $existing = ApplicationDocument::query()
                ->where('application_uuid', $application->uuid)
                ->where('document_checklist_item_id', $item->id)
                ->first();

            $path = $this->encryption->store($file);

            if ($existing) {
                $oldPath = $existing->storage_path;
                $existing->fill([
                    'status'            => DocumentStatus::SUBMITTED->value,
                    'storage_disk'      => $this->encryption->diskName(),
                    'storage_path'      => $path,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type'         => $file->getClientMimeType(),
                    'size_bytes'        => $file->getSize(),
                    'uploaded_via'      => $channel->value,
                    'uploaded_by'       => Auth::id(),
                    'uploaded_at'       => now(),
                    'rejection_reason'  => null,
                    'reviewed_at'       => null,
                    'reviewed_by'       => null,
                    'version'           => $existing->version + 1,
                ])->save();

                if ($oldPath) {
                    $this->encryption->delete($oldPath);
                }
                $doc = $existing->fresh();
            } else {
                $doc = ApplicationDocument::create([
                    'institution_id'             => $application->institution_id,
                    'campus_id'                  => $application->campus_id,
                    'application_uuid'           => $application->uuid,
                    'lead_uuid'                  => $application->lead_uuid,
                    'document_checklist_item_id' => $item->id,
                    'status'                     => DocumentStatus::SUBMITTED->value,
                    'storage_disk'               => $this->encryption->diskName(),
                    'storage_path'               => $path,
                    'original_filename'          => $file->getClientOriginalName(),
                    'mime_type'                  => $file->getClientMimeType(),
                    'size_bytes'                 => $file->getSize(),
                    'uploaded_via'               => $channel->value,
                    'uploaded_by'                => Auth::id(),
                    'uploaded_at'                => now(),
                    'version'                    => 1,
                ]);
            }

            event(new DocumentUploaded($doc));

            return $doc;
        });
    }

    public function submitForReview(ApplicationDocument $doc): ApplicationDocument
    {
        if (! in_array($doc->status, [DocumentStatus::SUBMITTED, DocumentStatus::REJECTED], true)) {
            throw new DomainException('Document must be submitted before it can enter review.');
        }
        $doc->status = DocumentStatus::UNDER_REVIEW;
        $doc->save();

        return $doc;
    }

    public function approve(ApplicationDocument $doc, ?string $comment = null): ApplicationDocument
    {
        if ($doc->status === DocumentStatus::NOT_SUBMITTED) {
            throw new DomainException('Cannot verify a document that has not been submitted.');
        }
        $doc->status = DocumentStatus::VERIFIED;
        $doc->reviewed_by = Auth::id();
        $doc->reviewed_at = now();
        $doc->rejection_reason = null;
        $doc->save();

        if ($comment) {
            $this->addComment($doc, $comment, applicantVisible: true);
        }
        event(new DocumentVerified($doc));

        return $doc;
    }

    public function reject(ApplicationDocument $doc, string $reason): ApplicationDocument
    {
        if ($doc->status === DocumentStatus::NOT_SUBMITTED) {
            throw new DomainException('Cannot reject a document that has not been submitted.');
        }
        $doc->status = DocumentStatus::REJECTED;
        $doc->rejection_reason = $reason;
        $doc->reviewed_by = Auth::id();
        $doc->reviewed_at = now();
        $doc->save();

        $this->addComment($doc, $reason, applicantVisible: true);
        event(new DocumentRejected($doc));

        return $doc;
    }

    public function requestReupload(ApplicationDocument $doc, string $reason): ApplicationDocument
    {
        return $this->reject($doc, $reason);
    }

    public function addComment(ApplicationDocument $doc, string $comment, bool $applicantVisible = false): ApplicationDocumentComment
    {
        return ApplicationDocumentComment::create([
            'institution_id'          => $doc->institution_id,
            'application_document_id' => $doc->id,
            'actor_id'                => Auth::id() ?? 0,
            'type'                    => $applicantVisible ? 'applicant_visible' : 'internal',
            'comment'                 => $comment,
        ]);
    }

    private function assertAllowed(DocumentChecklistItem $item, UploadedFile $file): void
    {
        $maxKb = $item->max_size_kb ?: (int) config('crm_documents.storage.max_size_kb', 10240);
        if ($file->getSize() > $maxKb * 1024) {
            throw new DomainException("File exceeds max size {$maxKb}KB.");
        }
        $allowed = $item->allowed_mime ?: (array) config('crm_documents.allowed_mime_defaults', []);
        if (! empty($allowed) && ! in_array($file->getClientMimeType(), $allowed, true)) {
            throw new DomainException('Mime type not allowed for this document type.');
        }
    }
}
