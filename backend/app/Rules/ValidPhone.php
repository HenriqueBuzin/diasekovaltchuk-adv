<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

final class ValidPhone implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || preg_match('/^(?:\D*\d){10,11}\D*$/', $value) !== 1) {
            $fail(trans('site.valid_phone'));
        }
    }
}
