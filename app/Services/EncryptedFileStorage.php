<?php

namespace App\Services;

use App\Models\CaseFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EncryptedFileStorage
{
    public function storeUploadedStatement(UploadedFile $file, CaseFile $caseFile): array
    {
        return $this->storeEncryptedBytes(
            caseFile: $caseFile,
            kind: 'statements',
            extension: 'bin',
            plaintext: $file->get(),
            originalFilename: $file->getClientOriginalName(),
            mimeType: $file->getClientMimeType(),
            sizeBytes: $file->getSize(),
        );
    }

    public function storeEncryptedBytes(
        CaseFile $caseFile,
        string $kind,
        string $extension,
        string $plaintext,
        ?string $originalFilename = null,
        ?string $mimeType = null,
        ?int $sizeBytes = null,
    ): array {
        $hash = hash('sha256', $plaintext);
        [$ciphertext, $meta] = $this->encrypt($plaintext);

        $objectKey = sprintf(
            'cases/%d/%s/%s.%s',
            $caseFile->getKey(),
            $kind,
            (string) Str::uuid(),
            $extension,
        );

        $disk = Storage::disk(config('analytica.storage_disk'));
        $disk->put($objectKey, $ciphertext);

        return [
            'file_path' => $objectKey,
            'hash_integrity' => $hash,
            'original_filename' => $originalFilename,
            'mime_type' => $mimeType,
            'size_bytes' => $sizeBytes,
            'encryption_alg' => 'AES-256-GCM',
            'encryption_meta' => $meta,
        ];
    }

    public function getDecryptedBytes(string $filePath, array $encryptionMeta): string
    {
        $disk = Storage::disk(config('analytica.storage_disk'));
        $ciphertext = $disk->get($filePath);

        return $this->decrypt($ciphertext, $encryptionMeta);
    }

    public function deleteFile(string $filePath): void
    {
        if ($filePath === '') {
            return;
        }

        $disk = Storage::disk(config('analytica.storage_disk'));
        if ($disk->exists($filePath)) {
            $disk->delete($filePath);
        }
    }

    /**
     * @return array{0:string,1:array{v:int,nonce:string,tag:string}}
     */
    private function encrypt(string $plaintext): array
    {
        $nonceBytes = (int) config('analytica.encryption.nonce_bytes', 12);
        $tagBytes = (int) config('analytica.encryption.tag_bytes', 16);
        $nonce = random_bytes($nonceBytes);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            config('analytica.encryption.algorithm', 'aes-256-gcm'),
            $this->key(),
            OPENSSL_RAW_DATA,
            $nonce,
            $tag,
            '',
            $tagBytes
        );

        if ($ciphertext === false) {
            throw new \RuntimeException('File encryption failed.');
        }

        return [
            $ciphertext,
            [
                'v' => 1,
                'nonce' => base64_encode($nonce),
                'tag' => base64_encode($tag),
            ],
        ];
    }

    private function decrypt(string $ciphertext, array $meta): string
    {
        $nonce = base64_decode((string) ($meta['nonce'] ?? ''), true);
        $tag = base64_decode((string) ($meta['tag'] ?? ''), true);

        if (!is_string($nonce) || $nonce === '' || !is_string($tag) || $tag === '') {
            throw new \InvalidArgumentException('Missing encryption metadata.');
        }

        $plaintext = openssl_decrypt(
            $ciphertext,
            config('analytica.encryption.algorithm', 'aes-256-gcm'),
            $this->key(),
            OPENSSL_RAW_DATA,
            $nonce,
            $tag
        );

        if ($plaintext === false) {
            throw new \RuntimeException('File decryption failed.');
        }

        return $plaintext;
    }

    private function key(): string
    {
        $configured = config('analytica.encryption_key');
        if (is_string($configured) && $configured !== '') {
            return hash('sha256', $configured, true);
        }

        $appKey = (string) config('app.key');
        if (str_starts_with($appKey, 'base64:')) {
            $decoded = base64_decode(substr($appKey, 7), true);
            if (is_string($decoded) && $decoded !== '') {
                return hash('sha256', $decoded, true);
            }
        }

        return hash('sha256', $appKey, true);
    }
}
