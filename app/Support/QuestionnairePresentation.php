<?php

namespace App\Support;

use Filament\Support\Icons\Heroicon;

final class QuestionnairePresentation
{
    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            'draft' => 'Draft',
            'in_review' => 'In review',
            'pending' => 'Pending review',
            'approved' => 'Approved',
            'rejected' => 'Changes requested',
            'invalidated' => 'Review invalidated',
            'published' => 'Published to review sandbox',
            'retired' => 'Retired',
            'review_sandbox' => 'Review sandbox',
            default => str((string) $status)->replace('_', ' ')->title()->toString(),
        };
    }

    public static function statusColor(?string $status): string
    {
        return match ($status) {
            'draft' => 'gray',
            'in_review', 'pending' => 'warning',
            'approved', 'published', 'review_sandbox' => 'success',
            'rejected', 'invalidated', 'retired' => 'danger',
            default => 'info',
        };
    }

    public static function statusIcon(?string $status): Heroicon
    {
        return match ($status) {
            'draft' => Heroicon::OutlinedPencilSquare,
            'in_review', 'pending' => Heroicon::OutlinedClock,
            'approved', 'published', 'review_sandbox' => Heroicon::OutlinedCheckCircle,
            'rejected', 'invalidated' => Heroicon::OutlinedExclamationTriangle,
            'retired' => Heroicon::OutlinedArchiveBox,
            default => Heroicon::OutlinedInformationCircle,
        };
    }

    public static function version(string $subject, int|string|null $version): string
    {
        return "{$subject} v{$version}";
    }
}
