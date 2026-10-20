<?php

namespace App\Services\Evidence;

use Illuminate\Validation\ValidationException;

class GoogleEvidenceLocatorParser
{
    public function parse(string $locator): ParsedEvidenceLocator
    {
        if (strlen($locator) > 2048) {
            $this->invalid();
        }

        $parts = parse_url(trim($locator));
        if (! is_array($parts) || ($parts['scheme'] ?? null) !== 'https' || isset($parts['user']) || isset($parts['pass']) || isset($parts['port']) || isset($parts['fragment'])) {
            $this->invalid();
        }

        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        $query = [];
        parse_str($parts['query'] ?? '', $query);

        if ($host === 'drive.google.com') {
            if (preg_match('#^/file/d/([A-Za-z0-9_-]{10,200})(?:/|$)#', $path, $matches) === 1 && $this->validShareQuery($query)) {
                return new ParsedEvidenceLocator('google_drive_file', $matches[1]);
            }
            if ($path === '/open' && isset($query['id']) && is_string($query['id']) && $this->validId($query['id']) && $this->validShareQuery(array_diff_key($query, ['id' => true]))) {
                return new ParsedEvidenceLocator('google_drive_file', $query['id']);
            }
            $this->invalid();
        }

        if ($host !== 'docs.google.com' || ! $this->validShareQuery($query)) {
            $this->invalid();
        }

        foreach (['document' => 'google_docs', 'spreadsheets' => 'google_sheets', 'presentation' => 'google_slides'] as $segment => $provider) {
            if (preg_match("#^/{$segment}/d/([A-Za-z0-9_-]{10,200})(?:/|$)#", $path, $matches) === 1) {
                return new ParsedEvidenceLocator($provider, $matches[1]);
            }
        }

        $this->invalid();
    }

    private function validId(string $id): bool
    {
        return preg_match('/^[A-Za-z0-9_-]{10,200}$/', $id) === 1;
    }

    private function validShareQuery(array $query): bool
    {
        if ($query === []) {
            return true;
        }

        return array_keys($query) === ['usp']
            && is_string($query['usp'])
            && in_array($query['usp'], ['drive_link', 'sharing'], true);
    }

    private function invalid(): never
    {
        throw ValidationException::withMessages([
            'locator' => 'Enter an individual Google Drive, Docs, Sheets, or Slides file link.',
        ]);
    }
}
