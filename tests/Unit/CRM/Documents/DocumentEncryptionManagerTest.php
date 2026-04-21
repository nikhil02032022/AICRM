<?php

declare(strict_types=1);

// BRD: CRM-DM-008 — Encrypted-at-rest round-trip.

use App\Services\CRM\Documents\DocumentEncryptionManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('encrypted_documents');
});

it('round-trips file contents through encryption', function () {
    $manager = new DocumentEncryptionManager('encrypted_documents');
    $file = UploadedFile::fake()->createWithContent('test.pdf', 'hello world contents');

    $path = $manager->store($file);
    $plain = $manager->read($path);

    expect($plain)->toBe('hello world contents');
    // Ensure the raw stored payload is NOT the plaintext.
    $raw = Storage::disk('encrypted_documents')->get($path);
    expect($raw)->not->toBe('hello world contents');
});

it('deletes encrypted files', function () {
    $manager = new DocumentEncryptionManager('encrypted_documents');
    $file = UploadedFile::fake()->createWithContent('x.txt', 'abc');
    $path = $manager->store($file);

    expect(Storage::disk('encrypted_documents')->exists($path))->toBeTrue();
    $manager->delete($path);
    expect(Storage::disk('encrypted_documents')->exists($path))->toBeFalse();
});
