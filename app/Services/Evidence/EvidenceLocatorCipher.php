<?php

namespace App\Services\Evidence;

use Illuminate\Encryption\Encrypter;
use RuntimeException;

class EvidenceLocatorCipher
{
    public function encrypt(ParsedEvidenceLocator $locator): array
    {
        $version = (string) config('evidence.current_key_version');
        $payload = json_encode(['provider' => $locator->providerCode, 'file_id' => $locator->fileId], JSON_THROW_ON_ERROR);

        return [
            'locator_ciphertext' => $this->encrypter($version)->encryptString($payload),
            'encryption_key_version' => $version,
            'locator_fingerprint' => hash_hmac('sha256', $locator->providerCode.':'.$locator->fileId, $this->fingerprintKey()),
        ];
    }

    public function decrypt(string $ciphertext, string $version): ParsedEvidenceLocator
    {
        $payload = json_decode($this->encrypter($version)->decryptString($ciphertext), true, flags: JSON_THROW_ON_ERROR);

        return new ParsedEvidenceLocator($payload['provider'], $payload['file_id']);
    }

    private function encrypter(string $version): Encrypter
    {
        return new Encrypter($this->key($version), 'AES-256-GCM');
    }

    private function key(string $version): string
    {
        $configured = config("evidence.keys.{$version}");
        if (is_string($configured) && $configured !== '') {
            $decoded = str_starts_with($configured, 'base64:') ? base64_decode(substr($configured, 7), true) : $configured;
            if (is_string($decoded) && strlen($decoded) === 32) {
                return $decoded;
            }
        }

        if (app()->environment(['local', 'testing', 'sandbox'])) {
            return hash('sha256', 'osds-evidence:'.$version.':'.config('app.key'), true);
        }

        throw new RuntimeException("Evidence encryption key {$version} is unavailable.");
    }

    private function fingerprintKey(): string
    {
        $configured = config('evidence.fingerprint_key');
        if (is_string($configured) && $configured !== '') {
            return $configured;
        }
        if (app()->environment(['local', 'testing', 'sandbox'])) {
            return hash('sha256', 'osds-evidence-fingerprint:'.config('app.key'), true);
        }

        throw new RuntimeException('Evidence fingerprint key is unavailable.');
    }
}
