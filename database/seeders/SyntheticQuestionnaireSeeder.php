<?php

namespace Database\Seeders;

use App\Models\DocumentaryRequirement;
use App\Models\EvidenceProviderType;
use App\Models\FormDefinition;
use App\Models\FormQuestion;
use App\Models\FormSection;
use App\Models\FormVersion;
use App\Models\OsdsModule;
use App\Models\PublicationManifest;
use App\Models\RequirementSetVersion;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class SyntheticQuestionnaireSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing', 'sandbox'])) {
            throw new RuntimeException('Synthetic questionnaire drafts are restricted to local, testing, and sandbox environments.');
        }
        $owner = User::role('OSDS')->firstOrFail();
        $providers = EvidenceProviderType::query()->where('is_enabled', true)->pluck('id');

        $configurations = [
            'registration' => [
                'questions' => [
                    ['association_type', 'single_choice', 'Association type', ['athletic_association' => 'Athletic association with HEI membership', 'private_organization' => 'Individual or private organization']],
                    ['organization_name', 'short_text', 'Organization name', []],
                    ['complete_address', 'long_text', 'Complete address', []],
                    ['representative_name', 'short_text', 'Designated representative', []],
                    ['representative_contact', 'telephone', 'Representative contact number', []],
                    ['organization_email', 'email', 'Organization email address (optional)', []],
                    ['private_organization_details', 'long_text', 'Private organization details', []],
                ],
                'requirements' => ['undertaking', 'constitution_and_bylaws', 'registration_certificate', 'operations_manual', 'athlete_eligibility', 'personnel_credentials', 'member_hei_list', 'participating_hei_moa', 'grievance_mechanism', 'coaches_roster'],
            ],
            'acsa' => [
                'questions' => [
                    ['organization_type', 'single_choice', 'Institution or organization type', ['hei' => 'Higher Education Institution', 'athletic_association' => 'Athletic Association', 'national_sports_association' => 'National Sports Association', 'private_organization' => 'Individual or Private Organization', 'government' => 'Government']],
                    ['activity_type', 'single_choice', 'Sports activity type', ['competition' => 'Competition', 'seminar' => 'Seminar or Workshop', 'training' => 'Training', 'conference' => 'Sports Conference or Summit', 'other' => 'Other']],
                    ['organization_name', 'short_text', 'Institution or organization name', []],
                    ['activity_title', 'short_text', 'Activity title (proposed)', []],
                    ['activity_start', 'date', 'Activity start date (proposed)', []],
                    ['activity_end', 'date', 'Activity end date (proposed)', []],
                    ['venue', 'short_text', 'Venue (proposed)', []],
                    ['expected_participants', 'integer', 'Expected participant count (proposed)', []],
                    ['activity_website', 'short_text', 'Activity website (optional)', []],
                    ['other_activity_details', 'long_text', 'Other activity details', []],
                ],
                'requirements' => ['undertaking', 'activity_calendar', 'safety_protocols', 'insurance_coverage', 'hei_coordination', 'coaches_roster'],
            ],
        ];

        foreach ($configurations as $moduleCode => $configuration) {
            $module = OsdsModule::query()->where('code', $moduleCode)->firstOrFail();
            if (PublicationManifest::query()->where('module_id', $module->id)->where('channel', 'applicant_sandbox')->whereDoesntHave('retirement')->exists()) {
                continue;
            }
            $definition = FormDefinition::query()->firstOrCreate(['module_id' => $module->id, 'key' => 'initial_application'], ['name' => $module->name.' provisional application', 'audience' => 'applicant', 'owner_id' => $owner->id]);
            $version = FormVersion::query()->where('form_definition_id', $definition->id)->where('state', 'draft')->orderBy('version_number')->first();
            $version ??= FormVersion::query()->create(['form_definition_id' => $definition->id, 'version_number' => ((int) FormVersion::query()->where('form_definition_id', $definition->id)->max('version_number')) + 1, 'state' => 'draft', 'created_by' => $owner->id]);
            $section = FormSection::query()->firstOrCreate(['form_version_id' => $version->id, 'key' => 'applicant_details'], ['title' => 'Applicant details', 'description' => 'Synthetic provisional questions for staff review only.', 'sort_order' => 1]);
            foreach ($configuration['questions'] as $index => [$key, $type, $label, $options]) {
                $optional = in_array($key, ['organization_email', 'activity_website'], true);
                $condition = match ($key) {
                    'private_organization_details' => ['question' => 'association_type', 'operator' => 'equals', 'value' => 'private_organization'],
                    'other_activity_details' => ['question' => 'activity_type', 'operator' => 'equals', 'value' => 'other'],
                    default => null,
                };
                $question = FormQuestion::query()->firstOrCreate(['form_version_id' => $version->id, 'key' => $key], ['form_section_id' => $section->id, 'type' => $type, 'label' => $label, 'is_required' => ! $optional, 'sort_order' => $index + 1, 'validation_rules' => [], 'condition_rules' => $condition]);
                foreach ($options as $optionKey => $optionLabel) {
                    $question->options()->firstOrCreate(['key' => $optionKey], ['label' => $optionLabel, 'sort_order' => $question->options()->count() + 1]);
                }
            }
            $requirementVersion = RequirementSetVersion::query()->where('module_id', $module->id)->where('state', 'draft')->orderBy('version_number')->first();
            $requirementVersion ??= RequirementSetVersion::query()->create(['module_id' => $module->id, 'version_number' => ((int) RequirementSetVersion::query()->where('module_id', $module->id)->max('version_number')) + 1, 'state' => 'draft', 'created_by' => $owner->id]);
            foreach ($configuration['requirements'] as $index => $key) {
                $requirement = DocumentaryRequirement::query()->firstOrCreate(['requirement_set_version_id' => $requirementVersion->id, 'key' => $key], ['label' => str($key)->replace('_', ' ')->title(), 'instructions' => 'Provide an individual Google file reference when evidence intake is implemented.', 'is_required' => true, 'minimum_evidence' => 1, 'maximum_evidence' => $key === 'participating_hei_moa' ? 20 : 1, 'sort_order' => $index + 1]);
                $requirement->providers()->syncWithoutDetaching($providers);
            }
            $optionalRequirement = DocumentaryRequirement::query()->firstOrCreate(
                ['requirement_set_version_id' => $requirementVersion->id, 'key' => 'additional_supporting_document'],
                ['label' => 'Additional supporting document', 'instructions' => 'Optional synthetic supporting document for manual testing.', 'is_required' => false, 'minimum_evidence' => 0, 'maximum_evidence' => 1, 'sort_order' => count($configuration['requirements']) + 1],
            );
            $optionalRequirement->providers()->syncWithoutDetaching($providers);
        }
    }
}
