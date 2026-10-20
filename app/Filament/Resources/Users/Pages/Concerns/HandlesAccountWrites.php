<?php

namespace App\Filament\Resources\Users\Pages\Concerns;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

trait HandlesAccountWrites
{
    protected function accountWrite(Closure $write): Model
    {
        try {
            return $write();
        } catch (AuthorizationException|ValidationException|HttpExceptionInterface $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $reference = (string) Str::uuid();
            Log::error('Account save failed', ['support_reference' => $reference, 'exception_class' => $exception::class]);
            throw ValidationException::withMessages(['data.name' => 'The account save could not be confirmed. Inspect the saved account before trying again. Support reference: '.$reference]);
        }
    }
}
