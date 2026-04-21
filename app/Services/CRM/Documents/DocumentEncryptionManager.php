<?php

declare(strict_types=1);

namespace App\Services\CRM\Documents;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

// BRD: CRM-DM-008 — Encrypted-at-rest storage wrapper.
final class DocumentEncryptionManager
{
    public function __construct(private ?string $disk = null)
    {
        $this->disk ??= (string) config('crm_documents.storage.disk', 'encrypted_documents');
    }

    public function disk(): Filesystem
    {
        return Storage::disk($this->disk);
    }

    public function diskName(): string
    {
        return $this->disk;
    }

    public function store(UploadedFile $file): string
    {
        $contents = file_get_contents($file->getRealPath());
        if ($contents === false) {
            throw new RuntimeException('Unable to read uploaded file.');
        }
        $path = sprintf('%s/%s.bin', date('Y/m'), Str::uuid()->toString());
        $this->disk()->put($path, Crypt::encryptString($contents));

        return $path;
    }

    public function read(string $path): string
    {
        $raw = $this->disk()->get($path);
        if ($raw === null) {
            throw new RuntimeException("Document missing: {$path}");
        }

        return Crypt::decryptString($raw);
    }

    public function delete(string $path): void
    {
        if ($this->disk()->exists($path)) {
            $this->disk()->delete($path);
        }
    }
}
