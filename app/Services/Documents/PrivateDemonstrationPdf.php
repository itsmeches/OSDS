<?php

namespace App\Services\Documents;

use Symfony\Component\Process\Process;

class PrivateDemonstrationPdf
{
    public function render(string $html, string $directory): string
    {
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Private storage unavailable');
        }
        $input = $directory.'/input.html';
        $output = $directory.'/rendered.pdf';
        if (file_put_contents($input, $html) === false) {
            throw new \RuntimeException('Private input unavailable');
        }
        try {
            $this->run('render', $input, $output);
        } finally {
            if (is_file($input)) {
                unlink($input);
            }
        }

        return $output;
    }

    public function inspect(string $path, array $expected = []): array
    {
        if (! is_file($path) || filesize($path) > config('demonstration_documents.max_bytes')) {
            throw new \RuntimeException('Private PDF unavailable');
        }
        $result = $this->run('inspect', $path);
        if (! ($result['markings'] ?? false) || ($result['pages'] ?? 0) < 1) {
            throw new \RuntimeException('Invalid PDF');
        }
        // Page markings are checked independently by the PDF parser. Remove
        // their repeated text before comparing identity that spans page breaks.
        $content = str_replace(['DRAFT — DEMONSTRATION ONLY', 'Synthetic test document. Not valid as an official CHED certificate or authorization.'], '', $result['text'] ?? '');
        $text = preg_replace('/\s+/u', '', $content);
        foreach ($expected as $value) {
            if (! str_contains($text, preg_replace('/\s+/u', '', $value))) {
                throw new \RuntimeException('PDF protected text mismatch');
            }
        }

        return ['page_count' => $result['pages'], 'byte_size' => filesize($path), 'checksum' => hash_file('sha256', $path)];
    }

    private function run(string $mode, string $input, string $output = ''): array
    {
        $p = new Process([config('demonstration_documents.node'), base_path('scripts/render-demonstration-pdf.mjs'), $mode, $input, $output, config('demonstration_documents.chrome') ?? ''], base_path(), timeout: 60);
        $p->mustRun();
        $out = $p->getOutput();
        $start = strpos($out, '{"pages":');

        return json_decode(substr($out, $start === false ? 0 : $start), true, flags: JSON_THROW_ON_ERROR);
    }
}
