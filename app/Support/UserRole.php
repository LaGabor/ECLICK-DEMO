<?php

declare(strict_types=1);

namespace App\Support;

enum UserRole: string
{
    case Admin = 'admin';
    case User = 'user';
}
