<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

final class AuthPassword
{
    public static function rule(): Password
    {
        return Password::min(8)
            ->numbers()
            ->symbols();
    }
}
