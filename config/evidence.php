<?php

return [
    'current_key_version' => env('EVIDENCE_CURRENT_KEY_VERSION', 'v1'),
    'keys' => [
        'v1' => env('EVIDENCE_ENCRYPTION_KEY_V1'),
        'v2' => env('EVIDENCE_ENCRYPTION_KEY_V2'),
        'v3' => env('EVIDENCE_ENCRYPTION_KEY_V3'),
    ],
    'fingerprint_key' => env('EVIDENCE_FINGERPRINT_KEY'),
];
