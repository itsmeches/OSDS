<?php

namespace Tests\Unit;

use App\Support\ApplicantApplicationGuidance as Guidance;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ApplicantApplicationGuidanceTest extends TestCase
{
    public static function states(): array
    {
        return [
            'draft' => ['draft', 'Draft — not submitted', 'required document links'],
            'submitted' => ['submitted', 'Submitted — awaiting screening', 'unless corrections are requested'],
            'review' => ['under_screening', 'Under staff review', 'current submission'],
            'correction' => ['correction_requested', 'Corrections requested', 'correction copy'],
            'resubmission' => ['resubmitted', 'Corrections submitted — awaiting screening', 'Earlier submissions remain preserved'],
            'approval' => ['approved', 'Approved — demonstration only', 'does not issue a certificate'],
            'rejection' => ['rejected', 'Rejected — demonstration only', 'No demonstration-document request'],
            'unknown' => ['unrecognized', 'Status unavailable', 'application history'],
        ];
    }

    #[DataProvider('states')]
    public function test_state_meaning_and_next_step_are_explicit(string $state, string $label, string $meaning): void
    {
        $this->assertSame($label, Guidance::label($state));
        $this->assertStringContainsString($meaning, Guidance::description($state));
    }

    public function test_publication_notice_does_not_replace_final_outcomes(): void
    {
        $this->assertNull(Guidance::publicationNotice(false));
        $this->assertStringContainsString('editing and resubmission are unavailable', Guidance::publicationNotice(true));
        $this->assertStringContainsString('does not issue a certificate', Guidance::description('approved'));
        $this->assertStringContainsString('final decision summary', Guidance::description('rejected'));
    }

    public function test_unknown_values_cannot_become_html_or_implied_actions(): void
    {
        $this->assertSame('Status unavailable', Guidance::label('<script>approved</script>'));
        $this->assertSame('Open the application history for the available details.', Guidance::description('reopened'));
        $this->assertSame('Status unavailable', Guidance::label(''));
        $this->assertSame('Status unavailable', Guidance::label(null));
        $this->assertSame('Open the application history for the available details.', Guidance::description(null));
    }

    public function test_review_guidance_does_not_infer_a_chief_or_director_stage(): void
    {
        $this->assertStringNotContainsString('Chief', Guidance::description('under_screening'));
        $this->assertStringNotContainsString('Director', Guidance::description('under_screening'));
    }
}
