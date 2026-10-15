<?php

namespace App\Services\Evidence;

final readonly class ParsedEvidenceLocator
{
    public function __construct(
        public string $providerCode,
        public string $fileId,
    ) {}

    public function canonicalUrl(): string
    {
        return match ($this->providerCode) {
            'google_drive_file' => "https://drive.google.com/file/d/{$this->fileId}/view",
            'google_docs' => "https://docs.google.com/document/d/{$this->fileId}/edit",
            'google_sheets' => "https://docs.google.com/spreadsheets/d/{$this->fileId}/edit",
            'google_slides' => "https://docs.google.com/presentation/d/{$this->fileId}/edit",
        };
    }
}
