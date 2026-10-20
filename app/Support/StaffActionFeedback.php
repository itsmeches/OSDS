<?php

namespace App\Support;

use Closure;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class StaffActionFeedback
{
    public static function run(Closure $operation): bool
    {
        try {
            $operation();

            return true;
        } catch (AuthorizationException|HttpExceptionInterface $exception) {
            throw $exception;
        } catch (ValidationException $exception) {
            // Only fixed, reviewed domain messages select these explanations.
            // Never echo an arbitrary exception message, SQL, or submitted value.
            $explanations = [
                'Only a pending review may be decided.' => 'This review has already been completed. Reload it to see the recorded outcome.',
                'Only an approved review can be published.' => 'Approve this exact configuration before publishing it.',
                'The approval no longer matches this configuration.' => 'This configuration changed after review. Ask OSDS to send the exact pair for a new review.',
                'Retire the active sandbox publication before publishing another.' => 'A staff preview is already active for this service. Open Published questionnaires and retire it before publishing a replacement.',
                'Retire the active applicant sandbox publication first.' => 'An applicant publication is already active for this service. Open Published questionnaires and retire it before publishing a replacement.',
                'The publication is already retired.' => 'This publication is already retired. Reload the list to see its current status.',
                'The reviewed configuration has changed. Submit it for review again.' => 'The candidate changed. Request a new review instead of approving this copy.',
            ];
            $message = collect($exception->errors())->flatten()->first(fn ($value): bool => is_string($value) && isset($explanations[$value]));
            Notification::make()->title('Action not completed')->body($explanations[$message] ?? 'Check the form and the saved record status. If someone changed this record, reload it before trying again.')->warning()->persistent()->send();
            throw $exception;
        } catch (Throwable $exception) {
            $reference = (string) Str::uuid();
            Log::error('Staff action could not be confirmed', ['support_reference' => $reference, 'exception_class' => $exception::class]);
            Notification::make()->title('Action could not be confirmed')->body('Check the saved record before trying again. If this continues, give support this reference: '.$reference)->danger()->persistent()->send();

            throw (new Halt)->rollBackDatabaseTransaction();
        }
    }
}
