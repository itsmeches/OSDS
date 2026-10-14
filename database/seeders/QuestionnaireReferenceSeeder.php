<?php

namespace Database\Seeders;

use App\Models\EvidenceProviderType;
use App\Models\OsdsModule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class QuestionnaireReferenceSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['registration' => 'Certificate of Registration', 'acsa' => 'Authority to Conduct Sports Activities'] as $code => $name) {
            OsdsModule::query()->firstOrCreate(['code' => $code], ['public_id' => (string) Str::uuid(), 'name' => $name, 'is_active' => true]);
        }
        foreach (['google_drive_file' => 'Google Drive file', 'google_docs' => 'Google Docs', 'google_sheets' => 'Google Sheets', 'google_slides' => 'Google Slides'] as $code => $name) {
            EvidenceProviderType::query()->firstOrCreate(['code' => $code], ['name' => $name, 'is_enabled' => true]);
        }
    }
}
