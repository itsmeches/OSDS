<?php

namespace App\Support;

final class ApplicantApplicationGuidance
{
    public static function label(?string $state): string
    {
        return match ($state) {
            'draft' => 'Draft — not submitted',
            'submitted' => 'Submitted — awaiting screening',
            'under_screening' => 'Under staff review',
            'correction_requested' => 'Corrections requested',
            'resubmitted' => 'Corrections submitted — awaiting screening',
            'approved' => 'Approved — demonstration only',
            'rejected' => 'Rejected — demonstration only',
            default => 'Status unavailable',
        };
    }

    public static function description(?string $state): string
    {
        return match ($state) {
            'draft' => 'Complete and save your questionnaire and required document links, then review and submit.',
            'submitted' => 'Your submitted copy is saved for review. You do not need to submit another copy unless corrections are requested.',
            'under_screening' => 'Authorized staff are reviewing your current submission. Any requested corrections and applicant-facing updates will appear in your history.',
            'correction_requested' => 'Read the requested changes, update your correction copy, save and resubmit.',
            'resubmitted' => 'Your revised copy has been sent. Earlier submissions remain preserved.',
            'approved' => 'The Director approved this submitted revision for the demonstration. This does not issue a certificate. Check the issuance-request workspace for availability.',
            'rejected' => 'Read the final decision summary in your history. No demonstration-document request is available from this rejected decision.',
            default => 'Open the application history for the available details.',
        };
    }

    public static function publicationNotice(bool $ended): ?string
    {
        return $ended
            ? 'Questionnaire availability ended. Saved information is preserved; editing and resubmission are unavailable under the current publication.'
            : null;
    }
}
