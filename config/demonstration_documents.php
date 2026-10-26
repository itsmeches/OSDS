<?php

return [
    'node' => env('DEMO_PDF_NODE', 'node'),
    'chrome' => env('DEMO_PDF_CHROME'),
    'timeout' => 60,
    'max_bytes' => 20 * 1024 * 1024,
    'max_pages' => 20,
];
