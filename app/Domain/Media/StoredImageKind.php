<?php

declare(strict_types=1);

namespace App\Domain\Media;

enum StoredImageKind: string
{
    case Product = 'product';
    case Receipt = 'receipt';
}
